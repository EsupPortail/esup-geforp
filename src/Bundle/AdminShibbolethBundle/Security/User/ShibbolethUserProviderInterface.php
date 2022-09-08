<?php

namespace App\Bundle\AdminShibbolethBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ShibbolethUserProviderInterface
 * @package App\Bundle\AdminShibbolethBundle\Security\User
 */
Interface ShibbolethUserProviderInterface extends UserProviderInterface{

    /**
     * @param array $credentials
     * @return mixed
     */
    public function loadUser(array $credentials);
}
