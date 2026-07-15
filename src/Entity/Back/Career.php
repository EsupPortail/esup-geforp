<?php

namespace App\Entity\Back;


use App\Entity\Core\AbstractCareer;
use App\Entity\Core\AbstractInscription;
use App\Entity\PersonTrait\PersonTrait;
use App\Form\Type\AbstractCareerType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Form\Type\AbstractTraineeType;
use App\Entity\Core\AbstractTrainee;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Core\User;

#[ORM\Entity]
class Career extends AbstractCareer
{
    

    /**
     * @return mixed
     */
    static public function getFormType(): string
    {
        return AbstractCareerType::class;
    }


}
