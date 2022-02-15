<?php

namespace CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Tests\Encoder\PasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class StrongPasswordValidator.
 */
class StrongPasswordValidator extends ConstraintValidator
{
	/**
	 * @var TokenStorageInterface
	 */
	private $tokenStorage;
	/**
	 * @var EncoderFactoryInterface
	 */
	private $encoderFactory;

	/**
	 * StrongPasswordValidator constructor.
	 *
	 * @param TokenStorageInterface   $tokenStorage
	 * @param EncoderFactoryInterface $encoderFactory
	 */
	public function __construct(TokenStorageInterface $tokenStorage, EncoderFactoryInterface $encoderFactory)
    {
        $this->tokenStorage = $tokenStorage;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($password, Constraint $constraint)
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\StrongPassword');
        }

        $user = $constraint->user ? $constraint->user : $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new ConstraintDefinitionException('The User object must implement the UserInterface interface.');
        }

        if (strlen($password) < $constraint->minLength) {
            $this->context->buildViolation($constraint->shortMessage)
                ->setParameter('%minLength%', $constraint->minLength)
                ->setCode(StrongPassword::TOO_SHORT)
                ->addViolation();

            return;
        }

        /** @var PasswordEncoder $passwordEncoder */
        $passwordEncoder = $this->encoderFactory->getEncoder($user);
        $maxPasswordLength = $passwordEncoder ? $passwordEncoder::MAX_PASSWORD_LENGTH : $constraint->maxLength;
        if (strlen($password) > $maxPasswordLength) {
            $this->context->buildViolation($constraint->longMessage)
                ->setParameter('%maxLength%', $maxPasswordLength)
                ->setCode(StrongPassword::TOO_LONG)
                ->addViolation();

            return;
        }

        $requirementsCount = 0;

        // contenir une lettre minuscule
        if (preg_match('/[a-z]/', $password)) {
            ++$requirementsCount;
        }

        // contenir une lettre majuscule
        if (preg_match('/[A-Z]/', $password)) {
            ++$requirementsCount;
        }

        // contenir un nombre
        if (preg_match('/[0-9]/', $password)) {
            ++$requirementsCount;
        }

        // contenir un des caractères suivants : + - * / , ; : ? . ! = % $ & " ' ( _ ) @ # { } | \ [ ] ;
        if (false !== strpbrk($password, '+-*/,;:?.!=%$&"\\\'(_)@#{}|\[]')) {
            ++$requirementsCount;
        }

        if ($requirementsCount < $constraint->minRequirementsCount) {
            $this->context->buildViolation($constraint->weakMessage)
                ->setCode(StrongPassword::TOO_WEAK)
                ->addViolation();

            return;
        }

        // ne pas inclure le prénom et/ou le nom de l’utilisateur
        $accessor = PropertyAccess::createPropertyAccessor();
        $translitPassword = $this->transliterate($password);

        $values = array();
        foreach ($constraint->forbiddenProperties as $name) {
            $values[] = $accessor->getValue($user, $name);
        }
        $values = array_filter($values);

        foreach ($values as $value) {
            $regex = preg_quote(strtolower($this->transliterate($value)));
            if ($value && preg_match('/'.$regex.'/ui', $translitPassword)) {
                $this->context->buildViolation($constraint->forbiddenMessage)
                    ->setParameter('%words%', join(', ', $values))
                    ->setCode(StrongPassword::CONTAINS_FORBIDDEN_PROPERTIES)
                    ->addViolation();

                return;
            }
        }
    }

    /**
     * @param $str
     *
     * @return string
     */
    private function transliterate($str)
    {
        $transliterator = \Transliterator::create(
            'NFD; [:Nonspacing Mark:] Remove; NFC;'
        );

        return $transliterator->transliterate($str);
    }
}
