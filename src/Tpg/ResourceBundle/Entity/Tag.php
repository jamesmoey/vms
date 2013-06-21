<?php
namespace Tpg\ResourceBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="tag")
 * @Gedmo\Tree(type="nested")
 */
class Tag {
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
    protected $name;

    /**
     * @var integer
     * @JMS\Accessor(getter="getCount")
     * @JMS\ReadOnly
     */
    protected $count = 0;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     * @JMS\ReadOnly
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     * @JMS\ReadOnly
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     * @JMS\ReadOnly
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Tpg\ResourceBundle\Entity\Tag", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\ReadOnly
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Tpg\ResourceBundle\Entity\Tag", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     * @JMS\ReadOnly
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Tpg\ResourceBundle\Entity\S3Resources", mappedBy="tag")
     * @JMS\Exclude
     * @var ArrayCollection
     */
    private $resources;

    public function __construct() {
        $this->resources = new ArrayCollection();
    }

    /**
     * @param mixed $children
     *
     * @return Tag
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param int $id
     *
     * @return Tag
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
     * @param mixed $lft
     *
     * @return Tag
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param mixed $lvl
     *
     * @return Tag
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * @param string $name
     *
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $parent
     *
     * @return Tag
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $rgt
     *
     * @return Tag
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param mixed $root
     *
     * @return Tag
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->resources->count();
    }

    /**
     * Add resources
     *
     * @param \Tpg\ResourceBundle\Entity\S3Resources $resources
     * @return Tag
     */
    public function addResource(\Tpg\ResourceBundle\Entity\S3Resources $resources)
    {
        $this->resources[] = $resources;
        return $this;
    }

    /**
     * Remove resources
     *
     * @param \Tpg\ResourceBundle\Entity\S3Resources $resources
     */
    public function removeResource(\Tpg\ResourceBundle\Entity\S3Resources $resources)
    {
        $this->resources->removeElement($resources);
        return $this;
    }

    /**
     * Get resources
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getResources()
    {
        return $this->resources;
    }
}