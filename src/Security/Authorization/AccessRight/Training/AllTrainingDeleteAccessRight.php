<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Training;

use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTraining;
use Sygefor\Bundle\CoreBundle\Model\SemesteredTraining;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;

class AllTrainingDeleteAccessRight extends AbstractAccessRight
{
    protected $supportedClass = array(
        AbstractTraining::class,
        AbstractSession::class,
        SemesteredTraining::class,
    );
    protected $supportedOperation = 'DELETE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Supprimer les formations de tous les centres';
    }
}
