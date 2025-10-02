<?php

namespace App\Validator\Constraints;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Class StrongPassword.
 */
final class StrongPassword extends Constraint
{
    /**
     * @var string
     */
    public const TOO_SHORT = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1999';

    /**
     * @var string
     */
    public const TOO_WEAK = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1998';

    /**
     * @var string
     */
    public const CONTAINS_FORBIDDEN_PROPERTIES = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1997';

    /**
     * @var string
     */
    public const TOO_LONG = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1995';

    /**
     * @var string
     */
    public const INVALID_PASSWORD = 'e2a3fb6e-7ddc-4210-8fbf-2ab345ce1996';

    public $user;

    public $minLength = 8;

    public $shortMessage = 'Le mot de passe doit contenir au moins %minLength% caractères';

    public $maxLength = PasswordHasherInterface::MAX_PASSWORD_LENGTH;

    public $longMessage = 'Le mot de passe doit contenir au plus %maxLength% caractères';

    public $minRequirementsCount = 3;

    public $weakMessage = "Le mot de passe n'est pas assez fort";

    public $forbiddenProperties = ['firstName', 'lastName'];

    public $forbiddenMessage = 'Le mot de passe ne doit pas contenir les mots suivants : %words%';

    public function validatedBy(): string
    {
        return self::class.'Validator';
    }
}
