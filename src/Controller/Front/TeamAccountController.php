<?php
/**
 * Created by PhpStorm.
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
 * @Security("is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_RESP')")
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
        $user = $this->getUser();
        // Récupération du user avec le format trainee
        $arTraineeUser = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $traineeUser = $arTraineeUser[0];

        // Recupération des agents dont on est responsable
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findBy(array('emailsup' => $user->getCredentials()['mail']));

        $upcoming = array();
        $upcomingIds = array();
        $past = array();
        $now = new \DateTime();

        if (!empty($arTrainee)) {
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
                    }
                }
            }
        }

        return array('user' => $traineeUser, 'upcoming' => $upcoming, 'past' => $past, 'upcomingIds' => implode(',', $upcomingIds));
    }

    /**
     * Trainees.
     *
     * @Route("/trainees", name="front.account.team.trainees")
     * @Template("Front/Account/team/trainees.html.twig")
     * @Method("GET")
     */
    public function teamtraineesAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        // Récupération du user avec le format trainee
        $arTraineeUser = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $traineeUser = $arTraineeUser[0];

        // Recupération des agents dont on est responsable
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findBy(array('emailsup' => $user->getCredentials()['mail']));

        return array('user' => $traineeUser, 'trainees' => $arTrainee);
    }

    /**
     * Registrations from one trainee.
     *
     * @Route("/trainee/{id}/registrations", name="front.account.team.trainee.registrations")
     * @Template("Front/Account/team/trainee-registrations.html.twig")
     * @Method("GET")
     */
    public function traineeregistrationsAction(Request $request, ManagerRegistry $doctrine, $id)
    {
        $user = $this->getUser();
        $arSup = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $sup = $arSup[0];

        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findById($id);
        $trainee = $arTrainee[0];

        $inscriptions = $trainee->getInscriptions();
        $upcoming = array();
        $upcomingIds = array();
        $past = array();
        $now = new \DateTime();
        foreach ($inscriptions as $inscription) {
            if ($inscription->getSession()->getDatebegin() < $now) {
                $past[] = $inscription;
                $inscription->upcoming = false;
            }
            else {
                $inscription->upcoming = true;
                $upcoming[] = $inscription;
                $upcomingIds[] = $inscription->getId();
            }
        }

        return array('user' => $sup, 'upcoming' => $upcoming, 'past' => $past, 'upcomingIds' => implode(',', $upcomingIds), 'trainee' => $trainee);
    }

}