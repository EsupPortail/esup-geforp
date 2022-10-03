<?php

namespace App\Entity;


use App\Entity\Core\AbstractOrganization;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity
 */
class Organization extends AbstractOrganization
{

}
