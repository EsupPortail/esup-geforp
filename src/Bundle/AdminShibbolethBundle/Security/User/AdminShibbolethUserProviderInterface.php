<?php

namespace App\Bundle\AdminShibbolethBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface AdminShibbolethUserProviderInterface
 * @package App\Bundle\AdminShibbolethBundle\Security\User
 */
Interface AdminShibbolethUserProviderInterface extends UserProviderInterface{

    /**
     * @param array $credentials
     * @return mixed
     */
    public function loadUser(array $credentials);
}
