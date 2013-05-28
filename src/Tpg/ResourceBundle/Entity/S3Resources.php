<?php
namespace Tpg\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  "s3_resources",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_idx", columns={"bucket", "`key`"})
 *  }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *  "video" = "Tpg\ResourceBundle\Entity\VideoS3Resources",
 *  "image" = "Tpg\ResourceBundle\Entity\ImageS3Resources",
 *  "audio" = "Tpg\ResourceBundle\Entity\AudioS3Resources",
 *  "other" = "Tpg\ResourceBundle\Entity\OtherS3Resources",
 * })
 * @ORM\HasLifecycleCallbacks()
 */
abstract class S3Resources {
    /**
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
     * @ORM\Column
     * @var string
     */
    protected $bucket;

    /**
     * @ORM\Column("`key`")
     * @var string
     */
    protected $key;

    /**
     * Version ID follow by the date of the upload
     *
     * @ORM\Column("version_id", type="json_array", nullable=true)
     * @var array
     */
    protected $versionId = [];

    /**
     * Record created datetime.
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
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
     * @Gedmo\Timestampable(on="update")
     *
     * @var \DateTime
     *
     * @JMS\ReadOnly
     */
    protected $updatedAt;

    /**
     * Create a new instance of this sub class.
     *
     * @param $mimeType
     * @return S3Resources
     */
    public static function newInstance($mimeType) {
        if (stripos($mimeType, 'video/') === 0) {
            return new VideoS3Resources();
        } else if (stripos($mimeType, 'image/') === 0) {
            return new ImageS3Resources();
        } else if (stripos($mimeType, 'audio/') === 0) {
            return new AudioS3Resources();
        } else {
            return new OtherS3Resources();
        }
    }

    /**
     * @param string $bucket
     *
     * @return S3Resources
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @param int $id
     *
     * @return S3Resources
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $key
     *
     * @return S3Resources
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param array $versionId
     *
     * @return S3Resources
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    /**
     * @return array
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * Add a version with added date
     *
     * @param $id
     * @param $date
     *
     * @return $this
     */
    public function addVersionId($id, $date) {
        $this->versionId[$id] = $date;
        return $this;
    }

    /**
     * Remove a version id
     * @param $id
     *
     * @return $this
     */
    public function removeVersionId($id) {
        unset($this->versionId[$id]);
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}