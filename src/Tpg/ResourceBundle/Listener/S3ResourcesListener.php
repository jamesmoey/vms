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
            'preUpdate',
            'postUpdate'
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
            if ($arg->hasChangedField('versionId') && $arg->getOldValue("versionId")) {
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

    public function postUpdate(LifecycleEventArgs $args) {
        /** @var S3Resources $entity */
        $entity = $args->getEntity();
        if ($entity instanceof S3Resources) {
            /** @var EntityManager $entityManager */
            $entityManager = $args->getEntityManager();
            if (stripos($entity->getMimeType(), 'video/') === 0) {
                $type = 'Tpg\ResourceBundle\Entity\VideoS3Resources';
            } else if (stripos($entity->getMimeType(), 'image/') === 0) {
                $type = 'Tpg\ResourceBundle\Entity\ImageS3Resources';
            } else if (stripos($entity->getMimeType(), 'audio/') === 0) {
                $type = 'Tpg\ResourceBundle\Entity\AudioS3Resources';
            } else {
                $type = 'Tpg\ResourceBundle\Entity\OtherS3Resources';
            }
            if (!$entity instanceof $type) {
                /** @var S3Resources $newResource */
                $newResource = new $type();
                $newResource->setBucket($entity->getBucket())
                    ->setKey($entity->getKey())
                    ->setMimeType($entity->getMimeType())
                    ->setVersionId($entity->getVersionId())
                    ->setCreatedAt($entity->getCreatedAt())
                    ->setUpdatedAt($entity->getUpdatedAt())
                ;
                $entityManager->persist($newResource);
                $entityManager->createQueryBuilder()
                    ->delete(get_class($entity), 'e')
                    ->where('e.id = :id')
                    ->getQuery()
                    ->execute([':id'=>$entity->getId()])
                ;
                $entityManager->flush();
            }
        }
    }
}