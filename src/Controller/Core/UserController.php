<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 13/03/14
 * Time: 15:18.
 */

namespace App\Controller\Core;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Core\User;
use App\Repository\UserRepository;
use App\Form\Type\AccountType;
use App\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @Route("/admin/users")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user.index")
     */
    public function indexAction()
    {
        /* @var EntityManager */
        $em = $this->get('doctrine')->getManager();
        $repository = $em->getRepository(User::class);

//        $organization = $this->get('security.context')->getToken()->getUser()->getOrganization();
//        $hasAccessRightForAll = $this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.user.all');
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $repository->createQueryBuilder('u');
/*        if (!$hasAccessRightForAll) {
            $queryBuilder->where('u.organization = :organization')
                ->setParameter('organization', $organization);
        } */

        $users = $queryBuilder->orderBy('u.username')->getQuery()->getResult();
        dump($users[0]);
        dump($users);

        return $this->render('Core/views/User/index.html.twig', array(
            'users' => $users,
            'isAdmin' => 1, //$this->getUser()->isAdmin(),
        ));
    }

    /**
     * @param User $user
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="user.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     * @ParamConverter("user", class="App:Core:User", options={"id" = "id"})
     *
     * @return User
     */
    public function viewAction(User $user)
    {
        return $user;
    }

    /**
     * @param Request $request
     *
     * @Route("/add", name="user.add")
     *
     * @return array|RedirectResponse
     */
    public function addAction(Request $request)
    {
        $user = new User();
//        $user->setOrganization($this->getUser()->getOrganization());
        $form = $this->createForm(UserType::class, $user);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);

                $scope = $form->get('accessRightScope')->getData();
                if ($scope) {
                    $getUserAccessRights = function ($scope, array $accessRights) {
                        if (!is_string($scope)) {
                            throw new \UnexpectedValueException('String expected, '.gettype($scope).' given.');
                        }
                        $availableExts = call_user_func(function () use (&$scope) {
                            switch ($scope) {
                                case 'own.view':   return ['.own.view'];
                                case 'own.manage': return ['.own'];
                                case 'all.view':   return ['.all.view', '.own.view'];
                                case 'all.manage': return ['.all', '.own', '.national'];
                                default:           return [];
                            }
                        });
                        $userAccessRights = [];
                        foreach ($accessRights as $accessRight) {
                            for ($i = 0, $count = count($availableExts); $i < $count; ++$i) {
                                if (strpos($accessRight, $availableExts[$i]) !== false || $scope === 'all.manage') {
                                    $userAccessRights[] = $accessRight;
                                }
                            }
                        }

                        return $userAccessRights;
                    };

                    $accessRights = array_keys($this->get('sygefor_core.access_right_registry')->getAccessRights());
                    $userAccessRights = $getUserAccessRights($scope, $accessRights);

                    $user->setAccessRights($userAccessRights);
                }

                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'L\'utilisateur a bien été ajouté.');

                return $this->redirect($this->generateUrl('user.index'));
            }
        }

        return $this->render('Core/views/User/edit.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
            'isAdmin' => 1, //$user->isAdmin(),
        ));
    }

    /**
     * @param Request $request
     * @param User    $user
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="user.edit", options={"expose"=true})
     * @ParamConverter("user", class="SygeforCoreBundle:User", options={"id" = "id"})
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, User $user)
    {
        $oldPwd = $user->getPassword();
        $form = $this->createForm(UserType::class, $user);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $newPwd = $form->get('plainPassword')->getData();
                if (isset($newPwd)) {
                    $user->setPassword(null);
                    $user->setPlainPassword($newPwd);
                } else {
                    $user->setPassword($oldPwd);
                }
                $this->getDoctrine()->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', 'L\'utilisateur a bien été mis à jour.');

                return $this->redirect($this->generateUrl('user.index'));
            }
        }

        return $this->render('Core/views/User/edit.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
            'isAdmin' => $user->isAdmin(),
        ));
    }

    /**
     * @param Request $request
     *
     * @Route("/account", name="user.account", options={"expose"=true})
     *
     * @return array|RedirectResponse
     */
    public function accountAction(Request $request)
    {
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($this->getUser());
        $oldUsername = $this->getUser()->getUsername();
        $oldPwd = $this->getUser()->getPassword();

        $form = $this->createForm(AccountType::class, $this->getUser());

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            // verify password is set if username has changed
            $newPwd = $form->get('plainPassword')->getData();
            if ($form->get('username')->getData() !== $oldUsername && $encoder->encodePassword($newPwd, $this->getUser()->getSalt()) !== $oldPwd) {
                $form->get('plainPassword')->get('first')->addError(new FormError('Le mot de passe est invalide'));
            }

            if ($form->isValid()) {
                if (isset($newPwd)) {
                    $this->getUser()->setPassword(null);
                    $this->getUser()->setPlainPassword($newPwd);
                } else {
                    $this->getUser()->setPassword($oldPwd);
                }

                $this->getDoctrine()->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été mis à jour.');

                return $this->redirect($this->generateUrl('user.account'));
            }
        }

        return $this->render('user/profil.html.twig', array(
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ));
    }

    /**
     * @Route("/{id}/access-rights", requirements={"id" = "\d+"}, name="user.access_rights", options={"expose"=true})
     * @ParamConverter("user", class="SygeforCoreBundle:User", options={"id" = "id"})
     */
    public function accessRightsAction(Request $request, User $user)
    {
        $builder = $this->createFormBuilder($user);
        $builder->add('accessRights', 'access_rights', array('label' => 'Droits d\'accès'));
        $form = $builder->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', "Les droits d'accès ont bien été enregistrés.");

                return $this->redirect($this->generateUrl('user.access_rights', array('id' => $user->getId())));
            }
        }

        return $this->render('user/accessRights.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
        ));
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="user.remove")
     * @ParamConverter("user", class="SygeforCoreBundle:User", options={"id" = "id"})
     */
    public function removeAction(Request $request, User $user)
    {
        if ($request->getMethod() === 'POST') {
            if ($user->isAdmin()) {
                $this->get('session')->getFlashBag()->add('error', 'L\'utilisateur actuel est administrateur et ne peut pas être supprimé.');

                return $this->redirect($this->generateUrl('user.edit', array('id' => $user->getId())));
            }
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'L\'utilisateur a bien été supprimé.');

            return $this->redirect($this->generateUrl('user.index'));
        }

        return $this->render('user/remove.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * @Route("/{id}/login", requirements={"id" = "\d+"}, name="user.login")
     *
     * @param User $loginAsUser
     *
     * @return RedirectResponse
     */
    public function loginAsAction(User $loginAsUser)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new AccessDeniedHttpException('You can\'t do this action');
        }
        $token = new UsernamePasswordToken($loginAsUser, null, 'user_db', $loginAsUser->getRoles());
        $this->container->get('security.context')->setToken($token);

        return $this->redirect($this->generateUrl('core.index'));
    }
}
