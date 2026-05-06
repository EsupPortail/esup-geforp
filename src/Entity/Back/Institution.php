<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/8/16
 * Time: 12:55 PM
 */

namespace App\Entity\Back;

use App\Entity\Core\AbstractInstitution;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use App\Form\Type\InstitutionType;

#[ORM\Table(name: 'institution')]
#[ORM\Entity]
class Institution extends AbstractInstitution
{
    // Siret établissement (ajouté pour générer le fichier pour le CPF)
    #[Groups(['Default', 'api', 'institution'])]
    #[ORM\Column(name: 'siret', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $siret = null;

    // Raison sociale établissement (ajoutée pour générer le fichier pour le CPF)
    #[Groups(['Default', 'api', 'institution'])]
    #[ORM\Column(name: 'rs', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $rs = null;

    /**
     * @return string
     */
    public function getSiret(): ?string
    {
        return $this->siret ;
    }

    /**
     * @param string $siret
     */
    public function setSiret(?string $siret): void
    {
        $this->siret = $siret;
    }

    /**
     * @return string
     */
    public function getRs(): ?string
    {
        return $this->rs ;
    }

    /**
     * @param string $rs
     */
    public function setRs(?string $rs): void
    {
        $this->rs = $rs;
    }

    public static function getFormType(): string
    {
        return InstitutionType::class;
    }

    /**
     * loadValidatorMetadata.
     *
     */
    public static function loadValidatorMetadata(ClassMetadata $classMetadata): void
    {
        $classMetadata->addPropertyConstraint('zip', new Assert\NotBlank(['message' => 'Vous devez renseigner un code postal.']));
        $classMetadata->addPropertyConstraint('city', new Assert\NotBlank(['message' => 'Vous devez renseigner une ville.']));
    }

}