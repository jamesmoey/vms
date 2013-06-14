<?php
namespace Tpg\ResourceBundle\Model;

use Tpg\ResourceBundle\Entity\Tag;
use JMS\Serializer\Annotation as JMS;

/**
 * Class TagDTO
 *
 * @package Tpg\ResourceBundle\Model
 */
class TagDTO {

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $parent;

    /**
     * @param mixed $name
     *
     * @return TagDTO
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $parent
     *
     * @return TagDTO
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
}