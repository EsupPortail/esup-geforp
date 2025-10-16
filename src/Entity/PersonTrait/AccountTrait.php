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
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Class AccountTrait.
 */
trait AccountTrait
{
    use PersonTrait;
    use CoordinatesTrait;

    /**
     * @ORM\Column(type="string", length=32)
     * @Ignore()
     */
    #[Ignore]
    #[ORM\Column(type: 'string', length: 32)]
    private mixed $salt;

    /**
     * string.
     *
     * @Ignore()
     */
    #[Ignore]
    private mixed $plainPassword;

    /**
     * @ORM\Column(type="string")
     * @Ignore()
     */
    #[Ignore]
    #[ORM\Column(type: 'string')]
    private mixed $password;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     * @Groups({"trainee"})
     */
    #[ORM\Column(name: 'is_active', type: 'boolean')]
    #[Groups(['trainee'])]
    private mixed $isactive;

    #[ORM\Column(name: 'shibboleth_persistent_id', type: 'string', nullable: true)]
    #[Groups(['api.token', 'api.profile'])]
    private ?string $shibbolethpersistentid;

    /**
     * @ORM\Column(name="data", type="array", nullable=true)
     * @Ignore()
     */
    #[Ignore]
    #[ORM\Column(name: 'data', type: 'simple_array', nullable: true)]
    private mixed $data;

    /**
     * @var bool
     * @Ignore()
     */
    #[Ignore]
    private bool $sendCredentialsMail = false;

    /**
     * @var mixed
     * @Ignore()
     * This properties is used to automatically send a activation link to the trainee.
     * true or array of options
     */
    #[Ignore]
    private mixed $sendActivationMail = false;

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
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

    public function setIsactive(mixed $isactive): void
    {
        $this->isactive = $isactive;
    }

    /**
     * @return string|null
     */
    public function getShibbolethpersistentid(): ?string
    {
        return $this->shibbolethpersistentid;
    }

    public function setShibbolethpersistentid(mixed $shibbolethpersistentid): void
    {
        $this->shibbolethpersistentid = $shibbolethpersistentid;
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
        return $this->isactive;
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
