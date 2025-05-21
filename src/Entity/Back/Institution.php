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