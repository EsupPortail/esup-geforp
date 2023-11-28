<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 10:42 AM
 */

namespace App\Controller\Front;

use App\BatchOperations\BatchOperationRegistry;
use App\BatchOperations\Generic\EmailingBatchOperation;
use App\Entity\Core\AbstractInscription;
use App\Entity\Back\Inscription;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\Emailtemplate;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Back\Organization;
use App\Form\Type\AuthorizationType;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

/**
 * This controller regroup actions related to registration.
 *
 * @Route("/account/team")
 * @Security("is_granted('ROLE_RESP')")
 */
class TeamAccountController extends AbstractController
{
    protected $inscriptionClass = Inscription::class;

    /**
     * Registrations.
     *
     * @Route("/registrations", name="front.account.team.registrations")
     * @Template("Front/Account/team/registrations.html.twig")
     * @Method("GET")
     */
    public function teamregistrationsAction(Request $request, ManagerRegistry $doctrine)
    {
        // Recup param pour l'activation du bouton de relance au N+1
        $relanceActif = $this->getParameter('relance_actif');

        $user = $this->getUser();

        // Recupération des agents dont on est responsable
        $tabUs = $doctrine->getRepository('App\Entity\Back\Trainee')->findBy(array('emailsup' => $user->getCredentials()['mail']));
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);

        $upcoming = array();
        $upcomingIds = array();
        $past = array();
        $now = new \DateTime();
        $sup = "vide";
        foreach ($arTrainee as $trainee) {
            $inscriptions = $trainee->getInscriptions();

            foreach ($inscriptions as $inscription) {
                if ($inscription->getSession()->getDatebegin() < $now) {
                    $past[] = $inscription;
                    $inscription->upcoming = false;
                } else {
                    $inscription->upcoming = true;
                    $upcoming[] = $inscription;
                    $upcomingIds[] = $inscription->getId();
                    if ($inscription->getInscriptionstatus()->getName() == "En attente") {
                        $sup = $inscription->getTrainee()->getFirstnamesup() ." ". $inscription->getTrainee()->getLastnamesup();
                    }
                }
            }
        }

        return array('user' => $user, 'upcoming' => $upcoming, 'past' => $past, 'upcomingIds' => implode(',', $upcomingIds), 'relance' => $relanceActif);
    }

}