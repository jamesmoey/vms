<?php
namespace Tpg\S3UploadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class UploadCompleteEvent extends Event {

    protected $versionId;
    protected $bucket;
    protected $key;

    /**
     * @param mixed $versionId
     *
     * @return UploadCompleteEvent
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

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