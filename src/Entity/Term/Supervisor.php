<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/17/16
 * Time: 5:34 PM.
 */
namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use App\Form\Type\SupervisorType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Responsable pédagogique.
 *
 */
#[ORM\Table(name: 'supervisor')]
#[ORM\Entity]
class Supervisor extends AbstractTerm implements VocabularyInterface, \Stringable
{
    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'first_name', type: \Doctrine\DBAL\Types\Types::STRING, length: 50, nullable: true)]
    protected ?string $firstName = null;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Assert\Email(message: 'Vous devez renseigner un email valide.')]
    #[ORM\Column(name: 'email', type: \Doctrine\DBAL\Types\Types::STRING, length: 128, nullable: true)]
    protected ?string $email = null;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'phone_number', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $phoneNumber = null;

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber($phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->getName();
    }

    function __toString(): string
    {
        return $this->getFullName();
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return SupervisorType::class;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName(): string
    {
        return 'Responsable pédagogique';
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }
}
