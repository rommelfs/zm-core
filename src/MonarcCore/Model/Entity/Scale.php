<?php

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scale
 *
 * @ORM\Table(name="scales")
 * @ORM\Entity
 */
class Scale extends AbstractEntity
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
     * @var smallint
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    protected $type;

    /**
     * @var smallint
     *
     * @ORM\Column(name="min", type="smallint", options={"unsigned":true})
     */
    protected $min;

    /**
     * @var smallint
     *
     * @ORM\Column(name="max", type="smallint", options={"unsigned":true})
     */
    protected $max;

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
     * @return Asset
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }


    public function getInputFilter(){
        if (!$this->inputFilter) {
            parent::getInputFilter();

            $this->inputFilter->add(array(
                'name' => 'min',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));

            $this->inputFilter->add(array(
                'name' => 'max',
                'required' => true,
                'allow_empty' => false,
                'continue_if_empty' => false,
                'filters' => array(),
                'validators' => array(
                    array(
                        'name' => 'IsInt',
                    ),
                ),
            ));
        }
        return $this->inputFilter;
    }
}

