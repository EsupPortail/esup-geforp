<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/06/14
 * Time: 14:54.
 */
namespace App\Utils\HumanReadable;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Accesses an object property using human readable objects and property names given in config
 * Class OpenTBSPropertyAccessor.
 */
final class HumanReadablePropertyAccessor implements \Stringable
{
    /** @var  HumanReadablePropertyAccessorFactory $accessorFactory */
    private HumanReadablePropertyAccessorFactory $accessorFactory;

    /**
     * @param $object
     * @param object $object
     */
    public function __construct(
        /**
         * @var object currently accessed objectg
         */
        private $object
    )
    {
    }

    /**
     * Return an array of accessors from the object properties.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        $catalog = $this->accessorFactory->getTermCatalog($this->object::class);
        $return = [];

        foreach ($catalog['fields'] as $name => $options) {

            if ((is_object($this->$name)) && $this->accessorFactory->hasEntry($this->$name::class)) {
                $return[$name] = $this->accessorFactory->getAccessor($this->$name)->toArray();
            } elseif (is_object($this->$name) && $this->$name::class === self::class) {
                $return[$name] = $this->$name->toArray();
            } elseif (empty($this->$name)) {
                $return[$name] = [];
            } else {
                $return[$name] = $this->$name;
            }
        }

        return $return;
    }

    /**
     * magic getter for property path.
     *
     * @param string $property a string on the form 'myObjectAlias.MypropertyAlias'
     *
     * @return mixed|null
     */
    public function __get(string $property)
    {
        $path = null;
        switch ($property) {
            case 'email':
                //specific behaviour for retrieving mail attached to an entity (such as trainee, inscription, trainer, ...)
                $mailPath = $this->accessorFactory->getMailPath($this->object::class);
                if ($mailPath !== null) {
                    $accessor = PropertyAccess::createPropertyAccessor();

                    return $accessor->getValue($this->object, $mailPath);
                }

                break;
            case 'emailSup':
                if(get_parent_class($this->object)=== \App\Entity\Core\AbstractInscription::class)
                    $path = 'trainee.emailSup';
                elseif(get_parent_class($this->object)=== \App\Entity\Core\AbstractTrainee::class)
                    $path='emailSup';

                $accessor = PropertyAccess::createPropertyAccessor();
                return $accessor->getValue($this->object, $path);
            case 'emailCorr':
                if(get_parent_class($this->object)=== \App\Entity\Core\AbstractInscription::class)
                    $path = 'trainee.emailCorr';
                elseif(get_parent_class($this->object)=== \App\Entity\Core\AbstractTrainee::class)
                    $path='emailCorr';

                $accessor = PropertyAccess::createPropertyAccessor();
                return $accessor->getValue($this->object, $path);
            default:
                //default behaviour
                //path
                $expl = explode('.', (string) $property);
                //path may or may not contains dots. In the former case we need to split it in prefix and suffix parts.
                if (count($expl) === 1) {
                    $prefix = $property;
                    $suffix = '';
                }
                else {
                    $prefix = $expl[0];
                    $suffix = implode('.', array_slice($expl, 1));
                }

                $path = $this->accessProperty($prefix);
                //trying to get property for path suffix
                try {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    if (empty($path)) {
                        return null;
                    }
                    $value = $accessor->getValue($this->object, $path);


                }
                catch (NoSuchPropertyException|UnexpectedTypeException) {
                    // asked property was not found in object
                    // (alias did not correspond to something that actually exits
                    // thus an explicit mention is returned and is displayed in result file
                    return 'Non défini';
                }

                // if suffix is not empty, we continue along path
                if ($suffix !== '') {
                    if (is_object($value) && $this->accessorFactory->hasEntry($value::class)) {
                        //new property accessor for object.

                        /** @var HumanReadablePropertyAccessor $nextAccessor */
                        $nextAccessor = $this->accessorFactory->getAccessor($value);
                        if ($nextAccessor) {
                            try {
                                return $nextAccessor->$suffix;
                            }
                            catch (\Exception) {
                                return 'Non défini';
                            }
                        }
                    }
                } elseif (is_object($value) && ($value::class === 'DateTime')) {
                    //we reached end of path
                    // Cas des dates : on ne cherche pas la classe
                } elseif (is_object($value) && $this->accessorFactory->hasEntry($value::class)) {
                    return $this->accessorFactory->getAccessor($value);
                } elseif ($value instanceof \Traversable) {
                    $arr = new ArrayCollection();
                    foreach ($value as $val) {
                        if ($this->accessorFactory->hasEntry($val::class)) {
                            $arr->add($this->accessorFactory->getAccessor($val));
                        }
                    }
                    
                    return $arr;
                }

                //an attempt of formatting is done
                return $this->format($prefix, $value);
        }
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        try {
            $this->__get($name);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * magic function for string conversion.
     *
     */
    public function __toString(): string
    {
        return '';
    }

    private function accessProperty($property): ?string
    {
        if (is_object($this->object)) {
            return $this->accessorFactory->getPropertyForAlias(get_class($this->object), $property);
        }

        return $property;
    }

    public function setAccessorFactory(mixed $accessorFactory): void
    {
        $this->accessorFactory = $accessorFactory;
    }

    /**
     * @return mixed
     */
    public function getAccessorFactory()
    {
        return $this->accessorFactory;
    }

    public function setObject(mixed $object): void
    {
        $this->object = $object;
    }

    /**
     * returns a formatted version of requested value. useful for date for the moment.
     *
     * @param $value
     *
     * @return mixed
     */
    private function format($prefix, $value)
    {
        $format = $this->accessorFactory->getFormatForAlias($this->object::class, $prefix);
        $type = $this->accessorFactory->getTypeForAlias($this->object::class, $prefix);
        if ($value instanceof \DateTime) {
            if ($format) {
                /* @var \DateTime $value */
                return $value->format($format);
            }
            return $value->format('d/m/Y');
        }
        if (is_bool($value)) {
            return $value ? 'oui' : 'non';
        }
        elseif (is_string($value) && $type === 'ckeditor') {
            return Html2Text::convert($value);
        }

        return $value;
    }

}
