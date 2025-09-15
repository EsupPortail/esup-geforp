<?php

namespace App\Bundle\ShibbolethBundle\Security\User;

use http\Exception\InvalidArgumentException;
use phpDocumentor\Reflection\Types\Mixed_;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

final class ShibbolethUserToken extends AbstractToken {

    /**
     * @param mixed $user
     * @throws InvalidArgumentException
     */
    public function __construct($user = null, array $attributes = [], array $roles = [] ) {
        if ($roles === [] && $user instanceof UserInterface) $roles = $user->getRoles();

        parent::__construct($roles);
        $this->setUser($user);
        $this->setAttributes($attributes);
    }

    public function getCredentials(): string
    {
        return '';
    }

    /**
     * Returns name for display. Default is the 'cn' attribute or principal name if not available.
     */
    function getDisplayName(): string
    {
        return ($this->hasAttribute('cn'))? $this->getAttribute('cn') : $this->getUserIdentifier();
    }
    
    /**
     * Returns common name of principal. This is an alias for the 'cn' attribute
     */
    function getCommonName(): Mixed_
    {
        return $this->getAttribute('cn');
    }
    
    /**
     * Returns full name of principal. This is an alias for commonName
     */
    function getFullName(): Mixed_
    {
        return $this->getAttribute('cn');
    }
    
    function getSurname(): Mixed_
    {
        return $this->getAttribute('sn');
    }
    
    function getGivenName(): Mixed_
    {
        return $this->getAttribute('givenName');
    }
    
    function getMail(): Mixed_
    {
        return $this->getAttribute('mail');
    }
    
    function getMails(): array
    {
        return $this->getArrayAttribute('mail');
    }
    
    function getUID(): Mixed_
    {
        return $this->getAttribute('uid');
    }
    
    function getAffiliation(): Mixed_
    {
        return $this->getAttribute('affiliation');
    }
    
    function getScopedAffiliation(): Mixed_
    {
        return $this->getAttribute('scopedAffiliation');
    }
    
    function hasAffiliation($value = null): bool
    {
        return $this->hasAttributeValue('affiliation',$value);
    }
    
    function hasScopedAffiliation($value = null): bool
    {
        return $this->hasAttributeValue('scopedAffiliation',$value);
    }
    
    function isMember($scope = null): bool
    {
        return (empty($scope))? $this->hasAffiliation('member'): $this->hasScopedAffiliation('member@'.$scope);
    }
    
    function isEmployee($scope = null): bool
    {
        return (empty($scope))? $this->hasAffiliation('employee'): $this->hasScopedAffiliation('employee@'.$scope);
    }
    
    function isStudent($scope = null): bool
    {
        return (empty($scope))? $this->hasAffiliation('student'): $this->hasScopedAffiliation('student@'.$scope);
    }
    
    function isStaff($scope = null): bool
    {
        return (empty($scope))? $this->hasAffiliation('staff'): $this->hasScopedAffiliation('staff@'.$scope);
    }
    
    function isFaculty($scope = null): bool
    {
        return (empty($scope))? $this->hasAffiliation('faculty'): $this->hasScopedAffiliation('faculty@'.$scope);
    }
    
    function getLogoutURL(): Mixed_
    {
        return $this->getAttribute('logoutURL');
    }
    
    /**
     * Returns attribute value. If it's a multivalue, the first value is returned
     */
    public function getAttribute(string $name): Mixed_{
        $value = parent::getAttribute($name);
        return (is_array($value)) ? $value[0] : $value;
    }

    /**
     * Returns an attribute as an array of values
     */
    public function getArrayAttribute(string $name): array {
        $value = parent::getAttribute($name);
        return (is_array($value)) ? $value : [$value];
    }
    
    /**
     * Returns true if attribute exists with given value, or if attribute exists
     * if given value is null.
     */
    function hasAttributeValue($name, $value = null): bool{
        if (!$this->hasAttribute($name)) return false;

        return empty($value) || in_array($value, $this->getArrayAttribute($name));
    }
    
       
}
