<?php

namespace App\Security\Authorization\AccessRight;

/**
 * This interface is used by SerializationListener to automatically add the user access rights
 * during the entity serialization.
 *
 * @see CoreBundle\Listener\SerializationListener
 */
interface SerializedAccessRights
{
}
