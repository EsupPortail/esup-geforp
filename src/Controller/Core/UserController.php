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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use PHPUnit\Util\Json;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Core\User;
use App\Repository\UserRepository;
use App\Form\Type\AccountType;
use App\Form\Type\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

#[Route(path: '/admin/users')]final class UserController extends AbstractController
{
    /**
     * @var int
     */
    private const int PAGE = 1;
    /**
     * @var int
     */
    private const int PAGE_SIZE = 100000;
    /**
     * @var string[]
     */
    private const array SORT = ['lastName.source'];
    /**
     * @var string
     */
    private const string FIELDS = '';

    #[Route(path: '/', name: 'user.index')]
    public function index(ManagerRegistry $managerRegistry, AccessRightRegistry $accessRightRegistry): \Symfony\Component\HttpFoundation\Response
    {
        /* @var EntityManager */
        $objectManager = $managerRegistry->getManager();
        $objectRepository = $objectManager->getRepository(User::class);

        $organization = $this->getUser()->getOrganization();
        $userAccessRights = $this->getUser()->getAccessRights();

        $hasAccessRightForAll = 0;
        if (in_array("sygefor_core.rights.user.all", $userAccessRights)) {
            $hasAccessRightForAll = 1;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $objectRepository->createQueryBuilder('u');
        if ($hasAccessRightForAll === 0) {
            $queryBuilder->where('u.organization = :organization')
                ->setParameter('organization', $organization);
        }

        $users = $queryBuilder->orderBy('u.username')->getQuery()->getResult();

        return $this->render('Core/views/User/index.html.twig', ['users' => $users, 'isAdmin' => $this->getUser()->isAdmin()]);
    }

    /**
     *
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     *
     * @return User
     */
    #[Rest\View(serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/{id}', name: 'user.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function view(User $user, ManagerRegistry $managerRegistry, int $id): User
    {
        $user = $managerRegistry->getRepository(User::class)->find($id);
        if (!$user) {
            throw new AccessDeniedHttpException();
        }
        return $user;
    }

    /**
     * @param ManagerRegistry $managerRegistry eppn
     * @param Request $request email
     * @param AccessRightRegistry $accessRightRegistry
     * @param string $eppn
     * @param string $email
     * @return Response
     */
    #[Route(path: '/add/{eppn}/{email}', name: 'user.add')]
    public function add(ManagerRegistry $managerRegistry, Request $request, AccessRightRegistry $accessRightRegistry, string $eppn, string $email): \Symfony\Component\HttpFoundation\Response
    {
        // Test si current user is admin
        $curUserRoles = $this->getUser()->getRoles();
        $key = in_array('ROLE_ADMIN', $curUserRoles, true);
        $curUserAdmin = $key !== false;

        $user = new User();
        $user->setUsername($eppn);
        $user->setEmail($email);
        $user->setPassword('xyz123456!');

        $curOrg = $this->getUser()->getOrganization();
        $user->setOrganization($curOrg);

        $form = $this->createForm(UserType::class, $user);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $dateTime = new \DateTime('now');
                $user->setLastLogin($dateTime);

                $em = $managerRegistry->getManager();
                $em->persist($user);

                $scope = $form->get('accessRightScope')->getData();
                if ($scope) {
                    //$accessRights = array_keys($this->get('sygefor_core.access_right_registry')->getAccessRights());
                    //$userAccessRights = $getUserAccessRights($scope, $accessRights);
                }

                // Droits et roles pour test
                $userAccessRights = ['a:0:{}'];
                $user->setAccessRights($userAccessRights);

                // Roles
                $isAdmin = $form['isAdmin']->getData();
                $roles = $isAdmin ? ['ROLE_ADMIN'] : ['a:0:{}'];

                $user->setRoles($roles);

                $em->flush();

                $this->addFlash('success', 'L\'utilisateur a bien été ajouté.');

                return $this->redirectToRoute('user.index');
            }
        }

        return $this->render('Core/views/User/edit.html.twig', ['form' => $form->createView(), 'curUserAdmin' => $curUserAdmin, 'user' => $user, 'isAdmin' => $user->isAdmin()]);
    }

    /**
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/searchadd', name: 'user.searchadd')]
    public function searchadd(ManagerRegistry $managerRegistry, Request $request, UserPasswordHasherInterface $userPasswordHasher): \Symfony\Component\HttpFoundation\Response
    {
        $filters = [];
        /** @var User $curUser */
        $curUser = $this->getUser();
        $institution = $curUser->getOrganization()->getInstitution();
        $defaultData = ['institution' => $institution, 'nom' => ""];

        // Fonction de recherche
        $traineeSearchRepository = new TraineeSearchRepository($managerRegistry);
        $etab = '';
        $form = $this->createForm(TraineeSearchType::class, $defaultData);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if (($form->isSubmitted()) && ($form->isValid())) {
                $institutionF = $form['institution']->getData();
                if (!empty($institutionF)) {
                    $etab = $institutionF->getName();
                }

                $keyword = $form['nom']->getData();
                $filters['institution.name.source'] = $etab;

                $resSearch = $traineeSearchRepository->getTraineesList($keyword = "", $filters, self::PAGE, self::PAGE_SIZE, self::SORT, (array)self::FIELDS);
                $trainees = $resSearch['items'];

                if (!is_string($keyword)) {
                    return $keyword;
                }

                // Tableau pour test si trainee est deja gestionnaire
                $tabTrainees = [];

                // On prepare la requete sur les utilisateurs
                $em = $managerRegistry->getManager();
                $repository = $em->getRepository(User::class);
               foreach ($trainees as $trainee) {
                   // On teste si le trainee est dejà gestionnaire
                   $rUser = $repository->findOneBy(['email' => $trainee]);
                    $tabTrainees[] = $rUser ? 1 : 0;
                }

                return $this->render('Core/views/User/searchResult.html.twig', ['user' => $curUser, 'isAdmin' => $curUser->isAdmin(), 'trainees' => $trainees, 'gest' => $tabTrainees]);

            }
        }

        return $this->render('Core/views/User/search.html.twig', ['form' => $form->createView(), 'user' => $curUser, 'isAdmin' => $curUser->isAdmin()]);
    }

    #[Route(path: '/{id}/edit', name: 'user.edit', requirements: ['id' => '\d+'], options: ['expose' => true])]
    public function edit(ManagerRegistry $managerRegistry, Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher, int $id): \Symfony\Component\HttpFoundation\Response
    {
        $user = $managerRegistry->getRepository(User::class)->find($id);
        if (!$user) {
            throw new AccessDeniedHttpException();
        }
        // Test si current user is admin
        $curUserRoles = $this->getUser()->getRoles();
        $key = array_search('ROLE_ADMIN', $curUserRoles, true);
        $curUserAdmin = $key !== false;

        $form = $this->createForm(UserType::class, $user);
        $roles = $user->getRoles();
        $key = array_search('ROLE_ADMIN', $roles, true);
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
                    if ($isAdmin) {
                        // on ne change rien
                    } else {
                        // on supprime le role 'admin'
                        unset($roles[$key]);
                        $user->setRoles($roles);
                    }
                } elseif ($isAdmin) {
                    // si le user n'était pas admin
                    // on ajoute le role 'admin' au user
                    $roles[] = 'ROLE_ADMIN';
                    $user->setRoles($roles);
                }

                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($user);
                $objectManager->flush();
                $this->addFlash('success', 'L\'utilisateur a bien été mis à jour.');//'success', 'L\'utilisateur a bien été mis à jour.'

                return $this->redirectToRoute('user.index');
            }
        }

        return $this->render('Core/views/User/edit.html.twig', ['form' => $form->createView(), 'curUserAdmin' => $curUserAdmin, 'user' => $user, 'isAdmin' => $user->isAdmin()]);
    }

    #[Route(path: '/account', name: 'user.account', options: ['expose' => true])]
    public function account(ManagerRegistry $managerRegistry, Request $request, UserPasswordHasherInterface $userPasswordHasher): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', 'Votre profil a bien été mis à jour.');//'success', 'Votre profil a bien été mis à jour.';

                return $this->redirectToRoute('user.account');
            }
        }

        return $this->render('Core/views/User/profil.html.twig', ['form' => $form->createView(), 'user' => $this->getUser()]);
    }

    #[Route(path: '/{id}/access-rights', name: 'user.access_rights', requirements: ['id' => '\d+'], options: ['expose' => true])]
    public function accessRights(Request $request, User $user, ManagerRegistry $managerRegistry, Security $security, int $id): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $user = $managerRegistry->getRepository(User::class)->find($id);

        if (!$user) {
            throw new AccessDeniedHttpException();
        }
        
        $formBuilder = $this->createFormBuilder($user);
        $formBuilder->add('accessRights', AccessRightType::class, ['label' => 'Droits d\'accès']);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRights = $user->getAccessRights();
            if (!empty($selectedRights) && is_object(reset($selectedRights))) {
                $user->setAccessRights(array_map(fn($right) => $right->getName(), $selectedRights));
            }
            $managerRegistry->getManager()->flush();
            $this->addFlash('success', "Les droits d'accès ont bien été enregistrés.");//'success', "Les droits d'accès ont bien été enregistrés.";

            return $this->render('Core/views/User/accessRights.html.twig', ['form' => $form->createView(), 'user' => $user]);
        }


        return $this->render('Core/views/User/accessRights.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    #[Route(path: '/{id}/remove', name: 'user.remove', requirements: ['id' => '\d+'])]
    public function remove(ManagerRegistry $managerRegistry,Request $request, User $user, int $id): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $user = $managerRegistry->getRepository(User::class)->find($id);
        if (!$user) {
            throw new AccessDeniedHttpException();
        }
        if ($request->getMethod() === 'POST') {
            if ($user->isAdmin()) {
                $this->getSubscribedServices();//'error', 'L\'utilisateur actuel est administrateur et ne peut pas être supprimé.';

                return $this->redirectToRoute('user.edit', ['id' => $user->getId()]);
            }

            $em = $managerRegistry->getManager();
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');//'success', 'L\'utilisateur a bien été supprimé.';

            return $this->redirectToRoute('user.index');
        }

        return $this->render('Core/views/User/remove.html.twig', ['user' => $user]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: '/{id}/login', name: 'user.login', requirements: ['id' => '\d+'])]
    public function loginAs(User $loginAsUser, TokenStorageInterface $tokenStorage): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (!$this->getUser()->isAdmin()) {
            throw new AccessDeniedHttpException("You can't do this action");
        }

        $usernamePasswordToken = new UsernamePasswordToken($loginAsUser, (string)'user_db', $loginAsUser->getRoles());
        $tokenStorage->setToken($usernamePasswordToken,(string)'user_db');
       // $this->container->get(TokenStorageInterface::class)->setToken($usernamePasswordToken);

        return $this->redirectToRoute('core.index');
    }
}
