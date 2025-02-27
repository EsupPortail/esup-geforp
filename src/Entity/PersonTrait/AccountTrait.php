<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/25/16
 * Time: 10:21 AM.
 */
namespace App\Entity\PersonTrait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\EventListener\Serializer;
use Symfony\Component\Serializer\Attribute\Groups;

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
    #[ORM\Column(type: 'string', length: 32)]
    private mixed $salt;

    /**
     * string.
     *
     * @Serializer\Exclude
     */
    private mixed $plainPassword;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Exclude
     */
    #[ORM\Column(type: 'string')]
    private mixed $password;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     * @Serializer\Groups({"trainee"})
     */
    #[ORM\Column(name: 'is_active', type: 'boolean')]
    #[Groups(['trainee'])]
    private mixed $isactive;

    #[ORM\Column(name: 'shibboleth_persistent_id', type: 'string', nullable: true)]
    #[Groups(['api.token', 'api.profile'])]
    private ?string $shibbolethPersistentId;
    
    /**
     * @ORM\Column(name="data", type="array", nullable=true)
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'data', type: 'json', nullable: true)]
    private mixed $data;

    /**
     * @var bool
     * @Serializer\Exclude
     */
    private bool $sendCredentialsMail = false;

    /**
     * @var mixed
     * @Serializer\Exclude
     * This properties is used to automatically send a activation link to the trainee.
     * true or array of options
     */
    private mixed $sendActivationMail = false;

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername($username): void
    {
        $this->email = $username;
    }

    /**
     * @return mixed
     */
    public function getSalt(): mixed
    {
        return $this->salt;
    }

    public function setSalt(mixed $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword(): mixed
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(mixed $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * @return mixed
     */
    public function getPassword(): mixed
    {
        return $this->password;
    }

    public function setPassword(mixed $password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getIsactive(): mixed
    {
        return $this->isactive;
    }

    public function setIsactive(mixed $isActive): void
    {
        $this->isactive = $isActive;
    }

    /**
     * @return string|null
     */
    public function getShibbolethpersistentid(): ?string
    {
        return $this->shibbolethPersistentId;
    }

    public function setShibbolethpersistentid(mixed $shibbolethPersistentId): void
    {
        $this->shibbolethPersistentId = $shibbolethPersistentId;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function isSendCredentialsMail(): bool
    {
        return $this->sendCredentialsMail;
    }

    /**
     * @param bool $sendCredentialsMail
     */
    public function setSendCredentialsMail(bool $sendCredentialsMail): void
    {
        $this->sendCredentialsMail = $sendCredentialsMail;
    }

    /**
     * @return mixed
     */
    public function getSendActivationMail(): mixed
    {
        return $this->sendActivationMail;
    }

    public function setSendActivationMail(mixed $sendActivationMail): void
    {
        $this->sendActivationMail = $sendActivationMail;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {

    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     */
    public function isAccountNonExpired(): bool
    {
        return true;
    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     */
    public function isAccountNonLocked(): bool
    {
        return true;
    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     */
    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    /**
     * @see Symfony\Component\Security\Core\User\AdvancedUserInterface
     *
     * @return bool
     */
    public function isEnabled(): bool
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
