<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\AccountRepository;
use KULeuven\ShibbolethBundle\Security\ShibbolethUserToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use KULeuven\ShibbolethBundle\Security\ShibbolethUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class ShibbolethUserProvider implements ShibbolethUserProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var AccountRepository
     */
    private $repository;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container, AccountRepository $repository)
    {
        $this->container = $container;
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        // force not found to use the createUser method
        throw new UsernameNotFoundException();
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->repository->refreshUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->repository->supportsClass($class);
    }

    /**
     * If no user was found based on persistentId, try to find it by email.
     */
    public function createUser(ShibbolethUserToken $token)
    {
        $em = $this->container->get('doctrine')->getManager();
        $email = $token->getAttribute('mail');
        $email = mb_strtolower($email, 'UTF-8');

        // try to find a proper persistent id
        $shibbolethId = self::computeShibbolethId($token);
        if (!$shibbolethId) {
            return null;
        }

        // try to find the user by email, and then by persistent id
        $user = $this->repository->findOneByShibbolethPersistentId($shibbolethId);
        if (!$user && $email) {
            $user = $this->repository->findOneByEmail($email);
        }

        if ($user) {
            $user->setShibbolethPersistentId($shibbolethId);
            $em->flush();

            return $user;
        }

        return null;
    }

    public static function computeShibbolethId(ShibbolethUserToken $token)
    {
	    $email = $token->getAttribute('mail');
	    $email = mb_strtolower($email, 'UTF-8');
	    $identityProvider = $token->getAttribute('identityProvider');
	    $persistentId = $token->getAttribute('persistent_id');
	    $targetedId = $token->getAttribute('targeted_id');
	    $eppn = $token->getAttribute('eppn');

	    // try to find a proper persistent id
	    $shibbolethId = $persistentId ? $persistentId : $targetedId;

	    // else, build a custom one with eppn
	    if (!$shibbolethId && $identityProvider && $eppn) {
		    $shibbolethId = $identityProvider.'!'.$eppn;
	    }
	    // else, build a custom one with identityProvider
	    else if (!$shibbolethId && !$eppn && $identityProvider && $email) {
		    $shibbolethId = $identityProvider.'!'.$email;
	    }

	    if (!$shibbolethId) {
		    $shibbolethId = $email;
	    }

	    return $shibbolethId;
    }
}
