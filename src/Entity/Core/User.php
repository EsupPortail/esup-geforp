<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UserRepository;
use App\Entity\Core\AbstractOrganization;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * BaseUser.
 *
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="simple_array")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization", inversedBy="users", cascade={"persist", "merge"})
     * @ORM\JoinColumn(nullable=true)
     * @Assert\NotNull(message="Vous devez renseigner un centre de rattachement.", groups={"organization"})
     */
    protected $organization;

    /**
     * @var string
     * @ORM\Column(name="access_rights", type="simple_array", nullable=true)
     */
    protected $accessRights;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accessRights = array();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization(AbstractOrganization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return mixed
     */
    public function getAccessRights()
    {
        return $this->accessRights;
    }

    /**
     * @param mixed $accessRights
     */
    public function setAccessRights($accessRights)
    {
        $this->accessRights = $accessRights ? $accessRights : array();
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * Override default method.
     *
     * @param string $emailCanonical
     *
     * @return $this|\FOS\CoreBundle\Model\UserInterface
     */
    public function setEmailCanonical($emailCanonical)
    {
        return parent::setEmailCanonical(strval(uniqid()).$emailCanonical);
    }
}
