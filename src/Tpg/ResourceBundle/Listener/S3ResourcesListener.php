<?php
namespace Tpg\ResourceBundle\Listener;

use Aws\S3\S3Client;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Monolog\Logger;
use Tpg\ResourceBundle\Entity\S3Resources;

class S3ResourcesListener implements EventSubscriber {
    /**
     * @var S3Client $s3
     */
    protected $s3;

    /** @var  Logger $logger */
    protected $logger;

    public function __construct($s3, $logger) {
        $this->s3 = $s3;
        $this->logger = $logger;
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
            'preUpdate'
        );
    }

    public function postRemove(LifecycleEventArgs $arg) {
        /** @var S3Resources $resource */
        $resource = $arg->getEntity();
        if ($resource instanceof S3Resources) {
            $this->s3->deleteObject([
                'Bucket' => $resource->getBucket(),
                'Key' => $resource->getKey()
            ]);
        }
    }

    public function preUpdate(PreUpdateEventArgs $arg) {
        /** @var S3Resources $resource */
        $resource = $arg->getEntity();
        if ($resource instanceof S3Resources) {
            if ($arg->hasChangedField('bucket') || $arg->hasChangedField('key')) {
                $this->s3->copyObject([
                    'Bucket' => $resource->getBucket(),
                    'Key' => $resource->getKey(),
                    'CopySource' => $arg->getOldValue('bucket').'/'.$arg->getOldValue('key')
                ]);
                $this->s3->deleteObject([
                    'Bucket' => $arg->getOldValue('bucket'),
                    'Key' => $arg->getOldValue('key')
                ]);
                $arg->setNewValue('versionId', json_encode([]));
            }
            if ($arg->hasChangedField('versionId')) {
                $oldVersionId = json_decode($arg->getOldValue("versionId"));
                $newVersionId = json_decode($arg->getNewValue("versionId"));
                $deletedVersions = array_diff_key($oldVersionId, $newVersionId);
                foreach ($deletedVersions as $id=>$date) {
                    $this->s3->deleteObject([
                        'Bucket' => $arg->getOldValue('bucket'),
                        'Key' => $arg->getOldValue('key').'?versionId='.$id
                    ]);
                }
            }
        }
    }
}