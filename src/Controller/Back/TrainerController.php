<?php

namespace App\Controller\Back;

use App\Controller\Core\AbstractTrainerController;
use App\Entity\Trainer;
use App\Controller\Core\AbstractInstitutionController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;



/**
 * @Route("/trainer")
 */
class TrainerController extends AbstractTrainerController
{
    protected $trainerClass = Trainer::class;
}