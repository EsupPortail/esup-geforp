<?php

namespace App\Controller\Api\Account;

use App\Entity\Inscription;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\Term\Inscriptionstatus;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\Term\Emailtemplate;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\Term\Publiposttemplate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * This controller regroup actions related to registration.
 *
 * @Route("/api/account")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
abstract class AbstractRegistrationAccountController extends AbstractController
{
    protected $inscriptionClass = AbstractInscription::class;

    /**
     * Checkout registrations cart.
     *
     * @Route("/checkout", name="api.account.checkout", defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"api", "api.inscription"})
     * @Method("POST")
     */
    public function checkoutAction(Request $request, $sessions = array())
    {
        /** @var AbstractTrainee $trainee */
        $trainee = $this->getUser();

        $sessions = empty($sessions) ? $request->get('sessions') : $sessions;
        if (!$sessions) {
            throw new BadRequestHttpException('You must provide a list of session id.');
        }

        /** @var EntityManager $em */
        $em = $this->get('doctrine')->getManager();
        $repository = $em->getRepository(AbstractSession::class);

        // query builder
        $qb = $repository->createQueryBuilder('s')
            ->where('s.id = :session')
            ->andWhere('s.registration >= :registration')
            ->setParameter('registration', AbstractSession::REGISTRATION_PRIVATE); // limitRegistrationDate is empty OR >= NOW

        // get all sessions
        foreach ($sessions as $key => $id) {
            /** @var AbstractSession $session */
            $session = $qb
                ->setParameter('session', $id)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$session) {
                throw new BadRequestHttpException('This session id is invalid : '.$id);
            }
            // check registrable
            if (!$session->isRegistrable()) {
                throw new AccessDeniedException('This session is not registrable : '.$id);
            }

            $sessions[$key] = $session;
        }
        // filter array
        $sessions = array_filter($sessions);

        // create inscriptions
        $inscriptions = array();
        $repository = $em->getRepository(AbstractInscription::class);
        foreach ($sessions as $session) {
            // try to find any existent inscription for this trainee
            /** @var AbstractInscription $inscription */
            $inscription = $repository->findOneBy(array(
                'session' => $session,
                'trainee' => $trainee,
            ));

            // if inscription do not exists OR the trainee desisted
            if (!$inscription) {
                // if not, create it
                if (!$inscription) {
                    $inscription = new $this->inscriptionClass();
                    $inscription->setTrainee($trainee);
                    $inscription->setSession($session);
                }
                $inscription->setInscriptionStatus(null); // reset the inscription status
                $em->persist($inscription);
                $inscriptions[] = $inscription;
            }
        }
        $em->flush();

        // send a recap to the trainee
        $count = count($inscriptions);
        if ($count) {
            $this->sendCheckoutNotification($inscriptions, $trainee);
        }

        // return created inscriptions
        return array('inscriptions' => $inscriptions);
    }

    /**
     * Registrations.
     *
     * @Route("/registrations", name="api.account.registrations", defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"api", "api.inscription"})
     * @Method("GET")
     */
    public function registrationsAction(Request $request)
    {
        /** @var AbstractTrainee $trainee */
        $trainee = $this->getUser();

        return $trainee->getInscriptions();
    }

    /**
     * Desist a registration.
     *
     * @Route("/registration/{id}/desist", name="api.account.registration.desist", defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"api", "api.inscription"})
     * @Method("POST")
     */
    public function desistAction($id, Request $request)
    {
        $trainee = $this->getUser();

        /** @var EntityManager $em */
        $em = $this->get('doctrine')->getManager();
        $repository = $em->getRepository($this->inscriptionClass);

        /** @var AbstractInscription $inscription */
        $inscription = $repository->findOneBy(array(
            'id' => $id,
            'trainee' => $trainee,
        ));

        if (!$inscription) {
            throw new NotFoundHttpException('Unknown registration.');
        }

        // check date
        if ($inscription->getSession()->getDateBegin() < new \DateTime()) {
            throw new BadRequestHttpException('You cannot desist from a past session.');
        }

        // check status
        if ($inscription->getInscriptionStatus()->getStatus() > Inscriptionstatus::STATUS_ACCEPTED) {
            throw new BadRequestHttpException('Your registration has already been rejected.');
        }

        // ok, let's go
        if ($inscription->getInscriptionStatus()->getStatus() === Inscriptionstatus::STATUS_PENDING) {
            // if the inscription is pending, just delete it
            $em->remove($inscription);
        } else {
            // else set the status to "Desist"
            $status = $this->getDesistInscriptionStatus($trainee);
            $inscription->setInscriptionStatus($status);
        }

        $em->flush();

        return array('desisted' => true);
    }

    /**
     * Download a authorization form.
     *
     * @Route("/registration/{ids}/authorization", name="api.account.registration.authorization")
     * @Method("GET")
     */
    public function authorizationAction($ids, Request $request)
    {
        $authorizationTemplate = $this->getDoctrine()->getRepository(Publiposttemplate::class)->findOneBy(array(
            'organization' => $this->getUser()->getOrganization(),
            'machineName' => 'authorization',
        ));

        if ($authorizationTemplate) {
            $mailingOperation = $this->get('sygefor_core.batch.publipost.inscription');
            $file = $mailingOperation->execute(explode(',', $ids), array('template' => $authorizationTemplate->getId()));

            return $mailingOperation->sendFile($file['fileUrl'], $authorizationTemplate->getName().'.odt', array('pdf' => true));
        }

        throw new NotFoundHttpException('No authorization template has been found');
    }

    /**
     * @param array           $inscriptions
     * @param AbstractTrainee $trainee
     */
    protected function sendCheckoutNotification($inscriptions, $trainee)
    {
        // send a recap to the trainee
        $inscriptionIdsByOrganization = array();
        foreach ($inscriptions as $inscription) {
            $inscriptionIdsByOrganization[$inscription->getSession()
                ->getTraining()
                ->getOrganization()
                ->getId()][] = $inscription->getId();
        }

        foreach ($inscriptionIdsByOrganization as $organizationId => $inscriptionIds) {
            /** @var AbstractOrganization $org */
            $org = $this->getDoctrine()->getRepository(AbstractOrganization::class)->find($organizationId);

            /** @var QueryBuilder $qb */
            $qb = $this->getDoctrine()->getRepository(Inscriptionstatus::class)->createQueryBuilder('s');
            /** @var Inscriptionstatus $inscriptionStatus */
            $inscriptionStatus = $qb
                ->andWhere('s.organization = :organization')
                ->orWhere('s.organization IS NULL')
                ->andWhere('s.status = :status')
                ->setParameter('status', Inscriptionstatus::STATUS_PENDING)
                ->setParameter('organization', $org)
                ->setMaxResults(1)
                ->getQuery()->execute();

            if ($inscriptionStatus) {
                /** @var Emailtemplate $checkoutEmailTemplate */
                $checkoutEmailTemplate = $this->getDoctrine()->getRepository(Emailtemplate::class)->findOneBy(
                    array(
                        'organization' => $this->getDoctrine()->getRepository(AbstractOrganization::class)->find($organizationId),
                        'inscriptionStatus' => $inscriptionStatus,
                    )
                );

                if ($checkoutEmailTemplate) {
                    $this->get('sygefor_core.batch.email')->execute(
                        $inscriptionIds,
                        array(
                            'targetClass' => $this->inscriptionClass,
                            'subject' => $checkoutEmailTemplate->getSubject(),
                            'cc' => $checkoutEmailTemplate->getCc(),
                            'message' => $checkoutEmailTemplate->getBody(),
                            'templateAttachments' => $checkoutEmailTemplate->getAttachmentTemplates(),
                            'typeUser' => get_class($this->getUser()),
                        )
                    );
                }
            }
        }
    }

    /**
     * @param AbstractTrainee $trainee
     *
     * @return Inscriptionstatus|null
     */
    protected function getDesistInscriptionStatus(AbstractTrainee $trainee)
    {
        $em = $this->getDoctrine()->getManager();
        $status = $em->getRepository(Inscriptionstatus::class)->findOneBy(array('machineName' => 'desist', 'organization' => null));
        if (!$status) {
            $status = $em->getRepository(Inscriptionstatus::class)->findOneBy(array('machineName' => 'desist', 'organization' => $trainee->getOrganization()));
        }

        return $status;
    }
}
