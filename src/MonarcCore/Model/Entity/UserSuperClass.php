<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User Super Class
 *
 * @ORM\Table(name="users")
 * @ORM\MappedSuperclass
 */
class UserSuperClass extends AbstractEntity
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
     * @var \DateTime
     *
     * @ORM\Column(name="date_start", type="date", nullable=true)
     */
    protected $dateStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_end", type="date", nullable=true)
     */
    protected $dateEnd;

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", nullable=true)
     */
    protected $status = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    protected $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    protected $password;

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
     * @var integer
     *
     * @ORM\Column(name="language", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $language;

    public function getInputFilter($partial = false)
    {

        if (!$this->inputFilter) {
            parent::getInputFilter($partial);
            $this->inputFilter->add(array(
                'name' => 'firstname',
                'required' => ($partial) ? false : true,
                'filters' => array(
                    array('name' => 'StringTrim',),
                ),
                'validators' => array(),
            ));
            $this->inputFilter->add(array(
                'name' => 'lastname',
                'required' => ($partial) ? false : true,
                'filters' => array(
                    array('name' => 'StringTrim',),
                ),
                'validators' => array(),
            ));

            $validators = array(
                array('name' => 'EmailAddress'),
            );
            if (!$partial) {
                $validators[] = array(
                    'name' => '\MonarcCore\Validator\UniqueEmail',
                    'options' => array(
                        'adapter' => $this->getDbAdapter(),
                        'id' => $this->get('id'),
                    ),
                );
            }

            $this->inputFilter->add(array(
                'name' => 'email',
                'required' => ($partial) ? false : true,
                'filters' => array(
                    array('name' => 'StringTrim',),
                ),
                'validators' => $validators
            ));

            $this->inputFilter->add(array(
                'name' => 'role',
                'required' => false,
            ));

            $this->inputFilter->add(array(
                'name' => 'password',
                'allowEmpty' => true,
                'continueIfEmpty' => true,
                'required' => false,
                'filters' => array(
                    array(
                        'name' => '\MonarcCore\Filter\Password',
                        'options' => array(
                            'salt' => $this->getUserSalt(),
                        ),
                    ),
                ),
                'validators' => array(),
            ));

            $this->inputFilter->add(array(
                'name' => 'language',
                'allowEmpty' => true,
                'continueIfEmpty' => true,
                'required' => false,
                'filters' => array(
                    array('name' => 'ToInt',),
                ),
                'validators' => array(),
            ));
        }
        return $this->inputFilter;
    }

    /**
     * @param $userSalt
     * @return $this
     */
    public function setUserSalt($userSalt)
    {
        $this->parameters['userSalt'] = $userSalt;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserSalt()
    {
        return isset($this->parameters['userSalt']) ? $this->parameters['userSalt'] : '';
    }
}

