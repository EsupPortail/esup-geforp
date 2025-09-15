<?php

namespace App\Validator\Constraints;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
//use Symfony\Component\Security\Core\Tests\Encoder\PasswordEncoder;
//use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class StrongPasswordValidator.
 */
final class StrongPasswordValidator extends ConstraintValidator
{
    private $passwordHasher;

    /**
  * StrongPasswordValidator constructor.
  *
  */
 public function __construct(private readonly TokenStorageInterface $tokenStorage)
 {
 }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        $user = $constraint->user ?: $this->tokenStorage->getToken()->getUser();
        if (null === $value) {
            return;
        }
        if ('' === $value) {
            return;
        }

        if (strlen((string) $value) < $constraint->minLength) {
            $this->context->buildViolation($constraint->shortMessage)
                ->setParameter('%minLength%', $constraint->minLength)
                ->setCode(StrongPassword::TOO_SHORT)
                ->addViolation();

            return;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $value)) {
            $this->context->buildViolation($constraint->invalidPasswordMessage)
                ->setCode(StrongPassword::INVALID_PASSWORD)
                ->addViolation();
            return;
        }

        $maxPasswordLength = $constraint->maxLength;
        if (strlen((string) $value) > $maxPasswordLength) {
            $this->context->buildViolation($constraint->longMessage)
                ->setParameter('%maxLength%', $maxPasswordLength)
                ->setCode(StrongPassword::TOO_LONG)
                ->addViolation();

            return;
        }

        $requirementsCount = 0;

        // contenir une lettre minuscule
        if (preg_match('#[a-z]#', (string) $value)) {
            ++$requirementsCount;
        }

        // contenir une lettre majuscule
        if (preg_match('#[A-Z]#', (string) $value)) {
            ++$requirementsCount;
        }

        // contenir un nombre
        if (preg_match('#\d#', (string) $value)) {
            ++$requirementsCount;
        }

        // contenir un des caractères suivants : + - * / , ; : ? . ! = % $ & " ' ( _ ) @ # { } | \ [ ] ;
        if (false !== strpbrk((string) $value, '+-*/,;:?.!=%$&"\\\'(_)@#{}|\[]')) {
            ++$requirementsCount;
        }

        if ($requirementsCount < $constraint->minRequirementsCount) {
            $this->context->buildViolation($constraint->weakMessage)
                ->setCode(StrongPassword::TOO_WEAK)
                ->addViolation();

            return;
        }

        // ne pas inclure le prénom et/ou le nom de l’utilisateur
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $translitPassword = $this->transliterate($value);

        $values = [];
        foreach ($constraint->forbiddenProperties as $name) {
            $values[] = $propertyAccessor->getValue($user, $name);
        }

        $values = array_filter($values);

        foreach ($values as $value) {
            $regex = preg_quote(strtolower($this->transliterate($value)));
            if ($value && preg_match('/'.$regex.'/ui', $translitPassword)) {
                $this->context->buildViolation($constraint->forbiddenMessage)
                    ->setParameter('%words%', implode(', ', $values))
                    ->setCode(StrongPassword::CONTAINS_FORBIDDEN_PROPERTIES)
                    ->addViolation();

                return;
            }
        }
    }

    /**
     * @param $str
     *
     */
    private function transliterate($str): string
    {
        $transliterator = \Transliterator::create(
            'NFD; [:Nonspacing Mark:] Remove; NFC;'
        );

        return $transliterator->transliterate($str);
    }
}
