<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/25/16
 * Time: 10:21 AM.
 */
namespace App\Entity\PersonTrait;

/**
 * Class AccountTrait.
 */
trait AccountTrait
{
    use PersonTrait;
    use CoordinatesTrait;

    /**
     * @ORM\Column(type="string", length=32)
     * @Serializer\Exclude
     */
    private $salt;

    /**
     * string.
     *
     * @Serializer\Exclude
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Exclude
     */
    private $password;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     * @Serializer\Groups({"trainee"})
     */
    private $isactive;

    /**
     * @ORM\Column(name="shibboleth_persistent_id", type="string", nullable=true)
     * @Serializer\Groups({"api.token", "api.profile"})
     */
    private $shibbolethpersistentid;

    /**
     * @ORM\Column(name="data", type="array", nullable=true)
     * @Serializer\Exclude
     */
    private $data;

    /**
     * @var bool
     * @Serializer\Exclude
     */
    private $sendCredentialsMail = false;

    /**
     * @var mixed
     * @Serializer\Exclude
     * This properties is used to automatically send a activation link to the trainee.
     * true or array of options
     */
    private $sendActivationMail = false;

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername($username)
    {
        $this->email = $username;
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param mixed $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param mixed $plainPassword
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getIsactive()
    {
        return $this->isactive;
    }

    /**
     * @param mixed $isActive
     */
    public function setIsactive($isActive)
    {
        $this->isactive = $isActive;
    }

    /**
     * @return mixed
     */
    public function getShibbolethpersistentid()
    {
        return $this->shibbolethpersistentid;
    }

    /**
     * @param mixed $shibbolethPersistentId
     */
    public function setShibbolethpersistentid($shibbolethPersistentId)
    {
        $this->shibbolethpersistentid = $shibbolethPersistentId;
    }
    
    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function isSendCredentialsMail()
    {
        return $this->sendCredentialsMail;
    }

    /**
     * @param bool $sendCredentialsMail
     */
    public function setSendCredentialsMail($sendCredentialsMail)
    {
        $this->sendCredentialsMail = $sendCredentialsMail;
    }

    /**
     * @return mixed
     */
    public function getSendActivationMail()
    {
        return $this->sendActivationMail;
    }

    /**
     * @param mixed $sendActivationMail
     */
    public function setSendActivationMail($sendActivationMail)
    {
        $this->sendActivationMail = $sendActivationMail;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {

    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     * @return bool
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     * @return bool
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     * @return bool
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isActive;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
