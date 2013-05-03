<?php
namespace Tpg\S3UploadBundle\Service;

use Aws\Common\Enum\DateFormat;
use Aws\S3\S3Client;
use Aws\S3\S3SignatureInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityRepository;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Resource\Model;
use Tpg\S3UploadBundle\Entity\Multipart;
use Doctrine\ORM\Event\LifecycleEventArgs;

class MultipartUpload implements EventSubscriber {

    /**
     * @var S3Client $s3
     */
    protected $s3;

    protected $bucket;

    public function __construct($s3, $bucket) {
        $this->s3 = $s3;
        $this->bucket = $bucket;
    }

    /**
     * Initialise S3 Multipart Upload
     *
     * @param Multipart $part
     *
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
     *
     * @return
     */
    public function getUploadSignature(Multipart $part, $partsNumbers, \DateTime $expire) {
        if (!is_array($partsNumbers)) {
            $partsNumbers = [$partsNumbers];
        }
        $authorizations = [];
        $expire->setTimezone(new \DateTimeZone('GMT'));
        $now = new \DateTime('now', new \DateTimeZone('GMT'));
        foreach ($partsNumbers as $partId) {
            $request = $this->s3->createRequest(
                RequestInterface::POST,
                '/'.$part->getBucket().$part->getKey().'?partNumber='.$partId.'&uploadId='.$part->getUploadId(),
                [
                    'Content-Type' => $part->getMimeType(),
                    'x-amz-date' => $now->format(DateFormat::RFC2822)
                ]
            );
            /** @var S3SignatureInterface $signature */
            $signature = $this->s3->getSignature();
            $authorizations[$partId] = 'AWS ' .
                $this->s3->getCredentials()->getAccessKeyId() . ':' .
                $signature->signString(
                    $signature->createCanonicalizedString($request, $expire->getTimestamp()),
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
        $completedPart = $part->getCompletedPart();
        $completedPart[$partNumber] = $etag;
        $part->setCompletedPart($completedPart);
        $part->setStatus(Multipart::IN_PROGRESS);
    }

    /**
     * Complete a
     * @param Multipart $part
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
        $model = $this->s3->completeMultipartUpload([
            'Key'       => $part->getKey(),
            'Bucket'    => $this->bucket,
            'UploadId'  => $part->getUploadId(),
            'Parts'     => $parts
        ]);
        $part->setUpdatedAt(new \DateTime('now', new \DateTimeZone('GMT')));
        $part->setStatus(Multipart::COMPLETED);
        return $model;
    }

    /**
     * Abort S3 Multipart Upload
     *
     * @param Multipart $part
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
}