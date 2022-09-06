<?php

namespace App\Bundle\ShibbolethBundle\Security\AdminUser;

use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ShibbolethUserProviderInterface
 * @package App\Bundle\ShibbolethBundle\Security\User
 */
Interface ShibbolethAdminUserProviderInterface extends UserProviderInterface{

    /**
     * @param array $credentials
     * @return mixed
     */
    public function loadUser(array $credentials);
}
