<?php
namespace Tpg\S3UploadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class UploadCompleteEvent extends Event {
    protected $bucket;
    protected $key;

    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }
}