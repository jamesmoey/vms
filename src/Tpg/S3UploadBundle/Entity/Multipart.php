<?php
namespace Tpg\S3UploadBundle\Entity;

use Aws\S3\Enum\CannedAcl;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Multipart
 *
 * @package Tpg\S3UploadBundle\Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="s3upload_multipart")
 * @ORM\HasLifecycleCallbacks()
 */
class Multipart {

    static $SIZE_PER_PART = 10485760;

    const STARTED = 0;
    const IN_PROGRESS = 1;
    const ABORTED = 2;
    const COMPLETED = 3;

    /**
     * S3 Multipart Upload ID
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer
     *
     * @JMS\ReadOnly
     */
    protected $id;

    /**
     * Amazon S3 Upload ID
     *
     * @ORM\Column(name="upload_id", type="string", unique=true)
     * @Assert\NotBlank(groups={"create"})
     *
     * @var string
     *
     * @JMS\ReadOnly
     */
    protected $uploadId;

    /**
     * Which part the upload is done?
     *
     * @ORM\Column(name="completed_part", type="json_array")
     *
     * @var array
     *
     * @JMS\ReadOnly
     * @JMS\Type("array<integer>")
     */
    protected $completedPart = [];

    /**
     * Size per part. Minimum of 5MB.
     *
     * @ORM\Column(name="size_per_part", type="integer")
     * @Assert\Range(min=5242880)
     * @Assert\NotNull(groups={"create"})
     *
     * @JMS\ReadOnly
     *
     * @var integer
     */
    protected $sizePerPart;

    /**
     * Total size of this upload.
     *
     * @ORM\Column(type="decimal")
     * @Assert\NotBlank()
     *
     * @var integer
     */
    protected $size;

    /**
     * Number of part for this upload. Maximum of 10,000 parts.
     *
     * @ORM\Column(name="number_of_part", type="integer")
     * @Assert\Range(min=1, max=10000)
     * @Assert\NotNull(groups={"create"})
     *
     * @JMS\ReadOnly
     *
     * @var integer
     */
    protected $numberOfPart;

    /**
     * Bucket of this upload.
     *
     * @ORM\Column
     * @Assert\NotBlank(groups={"create"})
     *
     * @var string
     *
     * @JMS\ReadOnly
     */
    protected $bucket;

    /**
     * Unique identification for this upload. Should map to a path in S3 bucket.
     *
     * @ORM\Column(name="`key`")
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $key;

    /**
     * Record created datetime.
     *
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @var \DateTime
     *
     * @JMS\ReadOnly
     */
    protected $createdAt;

    /**
     * Last record updated datetime.
     *
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @var \DateTime
     *
     * @JMS\ReadOnly
     */
    protected $updatedAt;

    /**
     * Status of this s3 multipart upload.
     *   0 - Started,
     *   1 - In Progress,
     *   2 - Abort,
     *   3 - Complete.
     *
     * @ORM\Column(type="integer", options={ "default" = 0 })
     *
     * @var integer
     *
     * @JMS\ReadOnly
     */
    protected $status = 0;

    /**
     * File mime type.
     *
     * @ORM\Column(name="mime_type", nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    protected $mimeType;

    /**
     * ACL for the resource. Default is private.
     * Valid options are,
     *   private,
     *   public-read
     *   public-read-write
     *   authenticated-read
     *   bucket-owner-read
     *   bucket-owner-full-control
     *
     * @ORM\Column(type="string", options={ "default" = "private" })
     *
     * @var string
     */
    protected $acl = "private";

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uploadId
     *
     * @param string $uploadId
     * @return Multipart
     */
    public function setUploadId($uploadId)
    {
        $this->uploadId = $uploadId;
    
        return $this;
    }

    /**
     * Get uploadId
     *
     * @return string 
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * Set size
     *
     * @param float $size
     * @return Multipart
     */
    public function setSize($size)
    {
        $this->size = $size;
    
        return $this;
    }

    /**
     * Get size
     *
     * @return float 
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set numberOfPart
     *
     * @param integer $numberOfPart
     * @return Multipart
     */
    public function setNumberOfPart($numberOfPart)
    {
        $this->numberOfPart = $numberOfPart;
    
        return $this;
    }

    /**
     * Get numberOfPart
     *
     * @return integer 
     */
    public function getNumberOfPart()
    {
        return $this->numberOfPart;
    }

    /**
     * Set bucket
     *
     * @param string $bucket
     * @return Multipart
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    
        return $this;
    }

    /**
     * Get bucket
     *
     * @return string 
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return Multipart
     */
    public function setKey($key)
    {
        $this->key = $key;
    
        return $this;
    }

    /**
     * Get key
     *
     * @return string 
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Multipart
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Multipart
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Multipart
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(0,1,2,3))) {
            throw new \UnexpectedValueException("Invalid Status: $status");
        }
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set acl
     *
     * @param string $acl
     * @return Multipart
     */
    public function setAcl($acl)
    {
        if (!in_array($acl, CannedAcl::values())) {
            throw new \UnexpectedValueException("$acl is not a valid ACL");
        }
        $this->acl = $acl;
    
        return $this;
    }

    /**
     * Get acl
     *
     * @return string 
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     * @return Multipart
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    
        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string 
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set completedPart
     *
     * @param array $completedPart
     * @return Multipart
     */
    public function setCompletedPart($completedPart)
    {
        $this->completedPart = $completedPart;

        return $this;
    }

    /**
     * Get completedPart
     *
     * @return array
     */
    public function getCompletedPart()
    {
        return $this->completedPart;
    }

    /**
     * Set sizePerPart
     *
     * @param integer $sizePerPart
     * @return Multipart
     */
    public function setSizePerPart($sizePerPart)
    {
        $this->sizePerPart = $sizePerPart;

        return $this;
    }

    /**
     * Get sizePerPart
     *
     * @return integer
     */
    public function getSizePerPart()
    {
        return $this->sizePerPart;
    }

    /**
     * Mark a part is done.
     * @param integer $part
     * @param string $etag
     */
    public function partDone($part, $etag) {
        $this->completedPart[$part] = $etag;
    }

    /**
     * Calculate number of part and size of each part
     */
    public function calculatePart() {
        if ($this->getSize() > self::$SIZE_PER_PART * 10000) {
            $this->setNumberOfPart(10000);
            $this->setSizePerPart(ceil($this->getSize() / 10000));
        } else {
            $this->setSizePerPart(self::$SIZE_PER_PART);
            $this->setNumberOfPart(ceil($this->getSize() / self::$SIZE_PER_PART));
        }
    }

    /**
     * Get array of incomplete part.
     *
     * @param int $length
     * @return int[] List of incomplete part.
     */
    public function getIncompletePart($length) {
        $incompleteParts = [];
        for ($i = 1, $numberOfPart = $this->getNumberOfPart(); $i <= $numberOfPart; $i++) {
            if (!array_key_exists($i, $this->getCompletedPart())) {
                $incompleteParts[] = $i;
            }
            if (count($incompleteParts) == $length) break;
        }
        return $incompleteParts;
    }
}