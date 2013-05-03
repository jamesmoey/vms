<?php
use Codeception\Util\Stub;

class MultipartTest extends \Codeception\TestCase\Test
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testCalculatePart1Part()
    {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setSize(1024*1024*10);
        $subject->calculatePart();
        $this->assertEquals($subject->getSizePerPart(), 1024*1024*10);
        $this->assertEquals($subject->getNumberOfPart(), 1);
    }

    public function testCalculatePartMoreThan1Part()
    {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setSize(1024*1025*105);
        $subject->calculatePart();
        $this->assertEquals($subject->getSizePerPart(), 1024*1024*10);
        $this->assertEquals($subject->getNumberOfPart(), 11);
        $this->assertGreaterThanOrEqual($subject->getSize(), $subject->getSizePerPart() * $subject->getNumberOfPart());
    }

    public function testCalculatePartGreaterThan10000Part()
    {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setSize(1024*1025*10*11000);
        $subject->calculatePart();
        $this->assertGreaterThanOrEqual($subject->getSize(), $subject->getSizePerPart() * $subject->getNumberOfPart());
        $this->assertEquals($subject->getSizePerPart(), 1024*1025*11);
        $this->assertEquals($subject->getNumberOfPart(), 10000);
    }

    public function testGetEmptyCompletePart() {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setNumberOfPart(100);
        $this->assertEquals($subject->getCompletedPart(), array());
        $this->assertEquals($subject->getIncompletePart(10), array(1,2,3,4,5,6,7,8,9,10));
    }

    public function testGetPartialEmptyCompletePart() {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setNumberOfPart(100);
        $subject->setCompletedPart(array(1,2,3,4,5));
        $this->assertEquals($subject->getIncompletePart(10), array(6,7,8,9,10,11,12,13,14,15));
    }

    public function testGetLastEmptyCompletePart() {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setNumberOfPart(10);
        $subject->setCompletedPart(array(1,2,3,4,5,6,7,8,9));
        $this->assertEquals($subject->getIncompletePart(10), array(10));
    }

    public function testPartDone() {
        $subject = new \Tpg\S3UploadBundle\Entity\Multipart();
        $subject->setNumberOfPart(10);
        $subject->partDone(1);
        $this->assertEquals($subject->getCompletedPart(), array(1));
    }
}