<?php

namespace App\Controller;

use App\Entity\Institution;
use App\Controller\Core\AbstractInstitutionController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;



/**
 * @Route("/institution")
 */
class InstitutionController extends AbstractInstitutionController
{
    protected $institutionClass = Institution::class;
}