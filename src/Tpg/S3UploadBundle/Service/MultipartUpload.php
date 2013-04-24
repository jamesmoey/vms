<?php
namespace Tpg\S3UploadBundle\Service;

use Aws\S3\S3Client;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityRepository;
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

    public function find($bucket, $key) {

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