<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 17/07/14
 * Time: 11:51.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Trainer;

use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainer;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;

class AllTrainerDeleteAccessRights extends AbstractAccessRight
{
    protected $supportedClass = AbstractTrainer::class;
    protected $supportedOperation = 'DELETE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Suppression des intervenants de tous les centres';
    }
}
