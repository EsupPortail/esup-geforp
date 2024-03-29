<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 13/03/14
 * Time: 15:18.
 */

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Form\Type\AccessRightType;
use App\Form\Type\TraineeSearchType;
use App\Repository\TraineeSearchRepository;
use ClassesWithParents\D;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/admin/users")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user.index")
     */
    public function indexAction(ManagerRegistry $doctrine, AccessRightRegistry $accessRightRegistry)
    {
        /* @var EntityManager */
        $em = $doctrine->getManager();
        $repository = $em->getRepository(User::class);

        $organization = $this->getUser()->getOrganization();
        $userAccessRights = $this->getUser()->getAccessRights();

        $hasAccessRightForAll = 0;
        if (in_array("sygefor_core.rights.user.all", $userAccessRights)) {
            $hasAccessRightForAll = 1;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $repository->createQueryBuilder('u');
        if (!$hasAccessRightForAll) {
            $queryBuilder->where('u.organization = :organization')
                ->setParameter('organization', $organization);
        }

        $users = $queryBuilder->orderBy('u.username')->getQuery()->getResult();

        return $this->render('Core/views/User/index.html.twig', array(
            'users' => $users,
            'isAdmin' => $this->getUser()->isAdmin(),
        ));
    }

    /**
     * @param User $user
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="user.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     * @ParamConverter("user", class="App\Entity\Core\User", options={"id" = "id"})
     *
     * @return User
     */
    public function viewAction(User $user)
    {
        return $user;
    }

    /**
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @param AccessRightRegistry $accessRightRegistry
     * @param null eppn
     * @param null email
     * @Route("/add/{eppn}/{email}", name="user.add")
     *
     * @return array|RedirectResponse
     */
    public function addAction(ManagerRegistry $doctrine, Request $request, AccessRightRegistry $accessRightRegistry, $eppn=null, $email=null)
    {
        $user = new User();
        $user->setUsername($eppn);
        $user->setEmail($email);
        $user->setPassword('xyz123456!');
        $curOrg = $this->getUser()->getOrganization();
        $user ->setOrganization($curOrg);

        $form = $this->createForm(UserType::class, $user);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $currentDate = new \DateTime('now');
                $user->setLastLogin($currentDate);

                $em = $doctrine->getManager();
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

                    //$accessRights = array_keys($this->get('sygefor_core.access_right_registry')->getAccessRights());
                    //$userAccessRights = $getUserAccessRights($scope, $accessRights);
                }

                // Droits et roles pour test
                $userAccessRights = ['a:0:{}'];
                $user->setAccessRights($userAccessRights);

                // Roles
                $isAdmin = $form['isAdmin']->getData();
                if($isAdmin) {
                    // on ajoute le role 'admin' au user
                    $roles = ['ROLE_ADMIN'];
                } else {
                    $roles = ['a:0:{}'];
                }
                $user->setRoles($roles);

                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'L\'utilisateur a bien été ajouté.');

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
     * @Route("/searchadd", name="user.searchadd")
     *
     * @return array|RedirectResponse
     */
    public function searchaddAction(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        /** @var User $curUser */
        $curUser = $this->getUser();
        $institution = $curUser->getOrganization()->getInstitution();
        $defaultData = array('institution' => $institution, 'nom' => "");

        // Fonction de recherche
        $traineeSearch = new TraineeSearchRepository($doctrine);

        $form = $this->createForm(TraineeSearchType::class, $defaultData);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if (($form->isSubmitted()) && ($form->isValid())) {
                $institutionF = $form['institution']->getData();
                if (!empty($institutionF)) {
                    $etab = $institutionF->getName();
                }
                $nom = $form['nom']->getData();

                $keyword = $nom;
                $filters['institution.name.source'] = $etab;
                $page = 1;
                $pageSize = 100000;
                $sort = array('lastName.source');
                $fields = '';

                $resSearch = $traineeSearch->getTraineesList($keyword, $filters, $page, $pageSize, $sort, $fields);
                $trainees = $resSearch['items'];

                // Tableau pour test si trainee est deja gestionnaire
                $tabTrainees = array();

                // On prepare la requete sur les utilisateurs
                $em = $doctrine->getManager();
                $repository = $em->getRepository(User::class);
                foreach($trainees as $trainee) {
                    // On teste si le trainee est dejà gestionnaire
                    $rUser = $repository->findByEmail($trainee->getEmail());
                    if($rUser)
                        $tabTrainees[] = 1;
                    else
                        $tabTrainees[] = 0;
                }

                return $this->render('Core/views/User/searchResult.html.twig', array(
                    'user' => $curUser,
                    'isAdmin' => $curUser->isAdmin(),
                    'trainees' => $trainees,
                    'gest' => $tabTrainees
                ));

            }
        }

        return $this->render('Core/views/User/search.html.twig', array(
            'form' => $form->createView(),
            'user' => $curUser,
            'isAdmin' => $curUser->isAdmin(),
        ));
    }

    /**
     * @param Request $request
     * @param User    $user
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="user.edit", options={"expose"=true})
     * @ParamConverter("user", class="App\Entity\Core\User", options={"id" = "id"})
     *
     * @return array|RedirectResponse
     */
    public function editAction(ManagerRegistry $doctrine, Request $request, User $user, UserPasswordHasherInterface $passwordHasher)
    {
        $form = $this->createForm(UserType::class, $user);
        $roles = $user->getRoles();
        $key = array_search('ROLE_ADMIN', $roles);
        if ($key !== false) {
            // si le user est admin, on coche la case du formulaire
            $form->get('isAdmin')->setData(true);
        } else {
            // si le user n'est pas admin, on decoche la case du formulaire
            $form->get('isAdmin')->setData(false);
        }

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $isAdmin = $form['isAdmin']->getData();

                if ($key !== false) {
                    // si le user etait admin
                    if($isAdmin) {
                        // on ne change rien
                    } else {
                        // on supprime le role 'admin'
                        unset($roles[$key]);
                        $user->setRoles($roles);
                    }
                } else {
                    // si le user n'était pas admin
                    if($isAdmin) {
                        // on ajoute le role 'admin' au user
                        $roles[] = 'ROLE_ADMIN';
                        $user->setRoles($roles);
                    } else {
                        // on ne change rien
                    }
                }

                $em = $doctrine->getManager();
                $em->persist($user);
                $em->flush();
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
    public function accountAction(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $doctrine->getManager()->persist($user);
                $doctrine->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été mis à jour.');

                return $this->redirect($this->generateUrl('user.account'));
            }
        }

        return $this->render('Core/views/User/profil.html.twig', array(
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ));
    }

    /**
     * @Route("/{id}/access-rights", requirements={"id" = "\d+"}, name="user.access_rights", options={"expose"=true})
     * @ParamConverter("user", class="App\Entity\Core\User", options={"id" = "id"})
     */
    public function accessRightsAction(Request $request, User $user, ManagerRegistry $doctrine, Security $security)
    {
        $accessReg = new AccessRightRegistry($security);
        // Transformation user rights
        $rights = $user->getAccessRights(); $newRights = [];
        foreach ($rights as $right) {
            $newRights[]= $accessReg->getByName($right);
        }
        $user->setAccessRights($newRights);

        $builder = $this->createFormBuilder($user);
        $builder->add('accessRights', AccessRightType::class, array('label' => 'Droits d\'accès'));
        $form = $builder->getForm();


        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // Transformation user rights
                $rights = $user->getAccessRights(); $newRights = [];
                foreach ($rights as $right) {
                    $newRights[]= $accessReg->getNameById($right);
                }
                $user->setAccessRights($newRights);
                $doctrine->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', "Les droits d'accès ont bien été enregistrés.");

                return $this->redirect($this->generateUrl('user.access_rights', array('id' => $user->getId())));
            }
        }

        return $this->render('Core/views/User/accessRights.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
        ));
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="user.remove")
     * @ParamConverter("user", class="App\Entity\Core\User", options={"id" = "id"})
     */
    public function removeAction(ManagerRegistry $doctrine,Request $request, User $user)
    {
        if ($request->getMethod() === 'POST') {
            if ($user->isAdmin()) {
                $this->get('session')->getFlashBag()->add('error', 'L\'utilisateur actuel est administrateur et ne peut pas être supprimé.');

                return $this->redirect($this->generateUrl('user.edit', array('id' => $user->getId())));
            }
            $em = $doctrine->getManager();
            $em->remove($user);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'L\'utilisateur a bien été supprimé.');

            return $this->redirect($this->generateUrl('user.index'));
        }

        return $this->render('Core/views/User/remove.html.twig', array(
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
