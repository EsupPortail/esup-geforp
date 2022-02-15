<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Inscription;

use Sygefor\Bundle\CoreBundle\Entity\AbstractInscription;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;

class AllInscriptionViewAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractInscription::class;
    protected $supportedOperation = 'VIEW';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Voir les inscriptions aux formations de tous les centres';
    }
}
