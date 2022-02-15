<?php

namespace CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * Class StrongPassword.
 */
class StrongPassword extends Constraint
{
    const TOO_SHORT = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1999';
    const TOO_WEAK = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1998';
    const CONTAINS_FORBIDDEN_PROPERTIES = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1997';
    const TOO_LONG = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1995';

    protected static $errorNames = array(
        self::TOO_SHORT => 'TOO_SHORT_ERROR',
        self::TOO_WEAK => 'TOO_WEAK_ERROR',
        self::CONTAINS_FORBIDDEN_PROPERTIES => 'CONTAINS_FORBIDDEN_PROPERTIES_ERROR',
        self::TOO_LONG => 'TOO_LONG_ERROR',
    );

    public $user = null;

    public $minLength = 8;
    public $shortMessage = 'Le mot de passe doit contenir au moins %minLength% caractères';

    public $maxLength = BasePasswordEncoder::MAX_PASSWORD_LENGTH;
    public $longMessage = 'Le mot de passe doit contenir au plus %maxLength% caractères';

    public $minRequirementsCount = 3;
    public $weakMessage = 'Le mot de passe n\'est pas assez fort';

    public $forbiddenProperties = array('firstName', 'lastName');
    public $forbiddenMessage = 'Le mot de passe ne doit pas contenir les mots suivants : %words%';

    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
