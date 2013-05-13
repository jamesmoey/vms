<?php
namespace Tpg\S3UploadBundle\Service;

use Aws\Common\Enum\DateFormat;
use Aws\S3\S3Client;
use Aws\S3\S3SignatureInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityRepository;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Exception\CommandException;
use Guzzle\Service\Resource\Model;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Tpg\S3UploadBundle\Entity\Multipart;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Tpg\S3UploadBundle\Event\UploadCompleteEvent;
use Tpg\S3UploadBundle\UploadEvents;

class MultipartUpload implements EventSubscriber {

    /**
     * @var S3Client $s3
     */
    protected $s3;

    protected $bucket;

    /** @var  Logger $logger */
    protected $logger;

    /** @var Multipart[] $completedUpload */
    protected $completedUpload = [];

    /** @var  ContainerAwareEventDispatcher $eventDispatcher*/
    protected $eventDispatcher;

    public function __construct($s3, $bucket, $logger, $ed) {
        $this->s3 = $s3;
        $this->bucket = $bucket;
        $this->logger = $logger;
        $this->eventDispatcher = $ed;
    }

    /**
     * Initialise S3 Multipart Upload
     *
     * @param Multipart $part
     * @throws CommandException
     * @return Model
     */
    public function initialiseUpload(Multipart $part) {
        /** @var Model $model */
        $model = $this->s3->createMultipartUpload(array(
            'ACL'       => $part->getAcl(),
            'Key'       => $part->getKey(),
            'Bucket'    => $this->bucket,
        ));
        $part->setBucket($model->get("Bucket"))
            ->setUploadId($model->get("UploadId"))
            ->setKey($model->get("Key"))
            ->setCreatedAt(new \DateTime('now', new \DateTimeZone('GMT')))
            ->setUpdatedAt(new \DateTime('now', new \DateTimeZone('GMT')));
        $part->calculatePart();
        return $model;
    }

    /**
     * Get Signature from S3
     *
     * @param Multipart $part
     * @param int[]|int $partsNumbers
     * @param \DateTime $expire
     * @param string $md5 Optional
     *
     * @return array with array of authorisations and x-amz-date
     */
    public function getUploadSignature(Multipart $part, $partsNumbers, \DateTime $expire, $md5 = null) {
        if (!is_array($partsNumbers)) {
            $partsNumbers = [$partsNumbers];
        }
        $authorizations = [];
        $expire->setTimezone(new \DateTimeZone('GMT'));
        $now = new \DateTime('now', new \DateTimeZone('GMT'));
        foreach ($partsNumbers as $partId) {
            $headers = [
                'Content-Type' => $part->getMimeType(),
                'x-amz-date' => $now->format(DateFormat::RFC2822)
            ];
            if ($md5 !== null) {
                $headers['Content-MD5'] = $md5;
            }
            $request = $this->s3->createRequest(
                RequestInterface::PUT,
                '/'.$part->getBucket().'/'.urlencode($part->getKey()).'?partNumber='.$partId.'&uploadId='.$part->getUploadId(),
                $headers
            );
            /** @var S3SignatureInterface $signature */
            $signature = $this->s3->getSignature();
            $authorizations[$partId] = 'AWS ' .
                $this->s3->getCredentials()->getAccessKeyId() . ':' .
                $signature->signString(
                    $signature->createCanonicalizedString($request),
                    $this->s3->getCredentials()
                );
        }
        return [
            'authorisations' => $authorizations,
            'x-amz-date' => $now->format(DateFormat::RFC2822)
        ];
    }

    /**
     * Complete a partial upload.
     *
     * @param Multipart $part
     * @param int       $partNumber
     * @param string    $etag
     */
    public function completePartial(Multipart $part, $partNumber, $etag) {
        $part->setUpdatedAt(new \DateTime('now', new \DateTimeZone('GMT')));
        $part->partDone($partNumber, $etag);
        $part->setStatus(Multipart::IN_PROGRESS);
    }

    /**
     * Complete a
     * @param Multipart $part
     *
     * @throws CommandException
     *
     * @return Model
     */
    public function completeUpload(Multipart $part) {
        $parts = [];
        foreach ($part->getCompletedPart() as $key => $etag) {
            $parts[] = [
                "PartNumber"    => $key,
                "ETag"          => $etag
            ];
        }
        /** @var Model $model */
        $model = $this->s3->completeMultipartUpload([
            'Key'       => $part->getKey(),
            'Bucket'    => $this->bucket,
            'UploadId'  => $part->getUploadId(),
            'Parts'     => $parts
        ]);
        $part->setUri($model->get("Location"));
        $part->setVersionId($model->get("VersionId"));
        $part->setExpiration($model->get("Expiration"));
        $part->setEtag(trim($model->get("ETag"), '"'));
        $part->setUpdatedAt(new \DateTime('now', new \DateTimeZone('GMT')));
        $part->setStatus(Multipart::COMPLETED);
        $this->completedUpload[] = $part;
        return $model;
    }

    /**
     * Abort S3 Multipart Upload
     *
     * @throws CommandException
     * @param Multipart $part
     * @return Model
     */
    public function abortUpload(Multipart $part) {
        $model = $this->s3->abortMultipartUpload(array(
            'UploadId'  => $part->getUploadId(),
            'Key'       => $part->getKey(),
            'Bucket'    => $this->bucket,
        ));
        $part->setUpdatedAt(new \DateTime('now', new \DateTimeZone('GMT')));
        $part->setStatus(Multipart::ABORTED);
        return $model;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    function getSubscribedEvents()
    {
        return array(
            'postRemove',
            'preUpdate',
        );
    }

    public function postRemove(LifecycleEventArgs $arg) {
        if ($arg->getEntity() instanceof Multipart) {
            /** @var Multipart $part */
            $part = $arg->getEntity();
            if ($part->getStatus() == Multipart::STARTED || $part->getStatus() == Multipart::IN_PROGRESS) {
                $this->abortUpload($part);
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $arg) {
        if ($arg->getEntity() instanceof Multipart) {
            /** @var Multipart $part */
            $part = $arg->getEntity();
            if (
                $part->getStatus() == Multipart::IN_PROGRESS &&
                count($part->getCompletedPart()) == $part->getNumberOfPart()
            ) {
                try {
                    $this->completeUpload($part);
                    $em = $arg->getEntityManager();
                    $uow = $em->getUnitOfWork();
                    $meta = $em->getClassMetadata(get_class($part));
                    $uow->recomputeSingleEntityChangeSet($meta, $part);
                } catch(CommandException $e) {
                    $this->logger->error("Error calling complete upload to AWS S3", ['exception'=>$e]);
                }
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $arg) {
        $entity = $arg->getEntity();
        if ($entity instanceof Multipart) {
            if (in_array($entity, $this->completedUpload)) {
                $event = new UploadCompleteEvent();
                $event->setBucket($entity->getBucket())
                    ->setKey($entity->getKey());
                $this->eventDispatcher->dispatch(UploadEvents::COMPLETE, $event);
            }
        }
    }
}