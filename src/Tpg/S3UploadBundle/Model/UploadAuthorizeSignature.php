<?php
namespace Tpg\S3UploadBundle\Model;

use JMS\Serializer\Annotation as JMS;

/**
 * Class UploadAuthorizeSignature
 *
 * @package Tpg\S3UploadBundle\Model
 *
 * @JMS\AccessType("public_method")
 */
class UploadAuthorizeSignature {
    /**
     * Key to the resource
     * @var string $key
     * @JMS\Type("string")
     */
    protected $key;
    /**
     * Bucket containing the resource
     * @var string $key
     * @JMS\Type("string")
     */
    protected $bucket;
    /**
     * Authorisation to upload thie resource
     * @var string $key
     * @JMS\Type("string")
     */
    protected $authorisation;
    /**
     * Date of this authorisation
     * @var \DateTime
     * @JMS\Type("DateTime<'D, d M Y H:i:s O'>")
     */
    protected $date;

    public function setAuthorisation($authorisation)
    {
        $this->authorisation = $authorisation;

        return $this;
    }

    public function getAuthorisation()
    {
        return $this->authorisation;
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

    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
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