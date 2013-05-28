<?php
namespace Tpg\S3UploadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class UploadCompleteEvent extends Event {

    protected $versionId;
    protected $bucket;
    protected $key;
    protected $record;

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

    /**
     * @param mixed $record
     *
     * @return UploadCompleteEvent
     */
    public function setRecord($record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecord()
    {
        return $this->record;
    }
}