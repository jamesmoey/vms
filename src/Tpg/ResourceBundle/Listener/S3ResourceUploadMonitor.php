<?php
namespace Tpg\ResourceBundle\Listener;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Guzzle\Service\Resource\Model;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Tpg\ResourceBundle\Entity\S3Resources;
use Tpg\S3UploadBundle\Event\UploadCompleteEvent;

class S3ResourceUploadMonitor {

    /**
     * @var S3Client $s3
     */
    protected $s3;

    /** @var  Logger $logger */
    protected $logger;

    /** @var  ContainerAwareEventDispatcher $eventDispatcher*/
    protected $eventDispatcher;

    /** @var EntityManager $em */
    protected $em;

    public function __construct($s3, $logger, $ed, $em) {
        $this->s3 = $s3;
        $this->logger = $logger;
        $this->eventDispatcher = $ed;
        $this->em = $em;
    }

    /**
     * @param UploadCompleteEvent $event
     */
    public function onUploadCompletion($event) {
        $bucket = $event->getBucket();
        $key = $event->getKey();
        /** @var S3Resources $resource */
        $resource = $this->em->getRepository("TpgResourceBundle:S3Resources")->findOneBy([
            'bucket'=>$bucket,
            'key'=>$key
        ]);
        if ($resource === null) {
            /** New Resource */
            /** @var Model $model */
            $model = $this->s3->headObject([
                'Bucket'=>$bucket,
                'Key'=>$key,
            ]);
            $resource = S3Resources::newInstance($model->get("ContentType"));
            $resource->setKey($key)
                ->setBucket($bucket);
            $this->em->persist($resource);
        }
        if ($event->getVersionId() !== null) {
            $resource->addVersionId($event->getVersionId(), new \DateTime());
        }
        $this->em->flush();
    }
}