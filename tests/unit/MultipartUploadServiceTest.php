<?php
use Codeception\Util\Stub;

class MultipartUploadServiceTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;

    /**
     * Build MultipartUpload service with empty stub.
     *
     * @param string $bucket
     * @param Aws\S3\S3Client $s3
     * @param Monolog\Logger $logger
     * @param Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
     *
     * @return \Tpg\S3UploadBundle\Service\MultipartUpload
     */
    protected function buildService($s3=null, $logger=null, $eventDispatcher=null,$bucket='test') {
        $service = new \Tpg\S3UploadBundle\Service\MultipartUpload(
            ($s3===null)?Stub::makeEmpty('Aws\S3\S3Client'):$s3,
            $bucket,
            ($logger===null)?Stub::makeEmpty('Monolog\Logger'):$logger,
            ($eventDispatcher===null)?Stub::makeEmpty('Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher'):$eventDispatcher
        );
        return $service;
    }

    public function testCompletePartial() {
        $part = new \Tpg\S3UploadBundle\Entity\Multipart();
        $part->setNumberOfPart(10);
        $service = $this->buildService();
        $service->completePartial($part, 1, 'testing...');
        $this->assertSame([1=>'testing...'], $part->getCompletedPart());
        $this->assertEquals(\Tpg\S3UploadBundle\Entity\Multipart::IN_PROGRESS, $part->getStatus());
    }

    public function testCompleteUpload() {
        $part = new \Tpg\S3UploadBundle\Entity\Multipart();
        $part->setNumberOfPart(2)
            ->setKey('key')
            ->setUploadId('id')
            ->setBucket('a')
            ->setCompletedPart([1=>'part1', 2=>'part2']);
        $s3 = $this->getMock('Aws\S3\S3Client', ['completeMultipartUpload'], [], '', false);
        $s3->expects($this->once())
            ->method('completeMultipartUpload')
            ->will($this->returnCallback(function($request) {
                $this->assertSame([
                    [ "PartNumber"    => 1, "ETag"          => 'part1' ],
                    [ "PartNumber"    => 2, "ETag"          => 'part2' ]
                ], $request['Parts']);
                $this->assertEquals("a", $request['Bucket']);
                $this->assertEquals("key", $request['Key']);
                $this->assertEquals("id", $request['UploadId']);
                return new \Guzzle\Service\Resource\Model([
                    'Location'=>'http://a.a/key',
                    'VersionId'=>'123',
                    'Expiration'=>'',
                    'ETag'=>'asd',
                ]);
            }));
        $service = $this->buildService($s3);
        $service->completeUpload($part);
        $this->assertEquals("a", $part->getBucket());
        $this->assertEquals("http://a.a/key", $part->getUri());
        $this->assertEquals(\Tpg\S3UploadBundle\Entity\Multipart::COMPLETED, $part->getStatus());
        $this->assertEquals("asd", $part->getEtag());
    }

    public function testRaiseCompleteEvent() {
        $part = new \Tpg\S3UploadBundle\Entity\Multipart();
        $part->setKey('key')
            ->setBucket('a');
        $arg = new \Doctrine\ORM\Event\LifecycleEventArgs($part, Stub::makeEmpty('Doctrine\ORM\EntityManager'));
        $eventDispatcher = $this->getMock(
            'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher',
            ['dispatch'],
            [],
            '',
            false
        );
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->will($this->returnCallback(function($type, $event) {
                $this->assertEquals(\Tpg\S3UploadBundle\UploadEvents::COMPLETE, $type);
                $this->assertEquals('a', $event->getBucket());
                $this->assertEquals('key', $event->getKey());
            }));
        $service = $this->buildService(null, null, $eventDispatcher);
        $property = (new ReflectionObject($service))->getProperty('completedUpload');
        $property->setAccessible(true);
        $property->setValue($service, [$part]);
        $service->postUpdate($arg);
    }

    public function testNoCompleteEventRaise() {
        $part = new \Tpg\S3UploadBundle\Entity\Multipart();
        $part->setKey('key')
            ->setBucket('a')
            ->setStatus(\Tpg\S3UploadBundle\Entity\Multipart::COMPLETED);
        $arg = new \Doctrine\ORM\Event\LifecycleEventArgs($part, Stub::makeEmpty('Doctrine\ORM\EntityManager'));
        $eventDispatcher = $this->getMock(
            'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher',
            ['dispatch'],
            [],
            '',
            false
        );
        $eventDispatcher->expects($this->never())
            ->method('dispatch');
        $service = $this->buildService(null, null, $eventDispatcher);
        $service->postUpdate($arg);
    }
}