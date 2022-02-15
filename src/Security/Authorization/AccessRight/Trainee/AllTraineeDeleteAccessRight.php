<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Trainee;

use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;

class AllTraineeDeleteAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractTrainee::class;
    protected $supportedOperation = 'DELETE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Supprimer les stagiaires de tous les centres';
    }
}
