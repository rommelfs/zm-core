<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Object Object
 *
 * @ORM\Table(name="objects_objects")
 * @ORM\Entity
 */
class ObjectObject extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="anr_id", type="integer", nullable=true)
     */
    protected $anr;

    /**
     * @var \MonarcCore\Model\Entity\Object
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Object", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="father_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $father;

    /**
     * @var \MonarcCore\Model\Entity\Object
     *
     * @ORM\ManyToOne(targetEntity="MonarcCore\Model\Entity\Object", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="child_id", referencedColumnName="id", nullable=true)
     * })
     */
    protected $child;

    /**
     * @var string
     *
     * @ORM\Column(name="creator", type="string", length=255, nullable=true)
     */
    protected $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="updater", type="string", length=255, nullable=true)
     */
    protected $updater;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Model
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Object
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * @param Object $father
     * @return ObjectObject
     */
    public function setFather($father)
    {
        $this->father = $father;
        return $this;
    }

    /**
     * @return Object
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param Object $child
     * @return ObjectObject
     */
    public function setChild($child)
    {
        $this->child = $child;
        return $this;
    }


}
