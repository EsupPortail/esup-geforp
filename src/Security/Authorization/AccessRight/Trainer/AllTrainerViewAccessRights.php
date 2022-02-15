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

class AllTrainerViewAccessRights extends AbstractAccessRight
{
    protected $supportedClass = AbstractTrainer::class;
    protected $supportedOperation = 'VIEW';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Voir les intervenants de tous les centres';
    }
}
