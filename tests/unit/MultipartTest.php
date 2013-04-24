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
}