<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/06/14
 * Time: 14:54.
 */

namespace CoreBundle\Utils\HumanReadable;

use Html2Text\Html2Text;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

/**
 * Accesses an object property using human readable objects and property names given in config
 * Class OpenTBSPropertyAccessor.
 */
class HumanReadablePropertyAccessor
{
    /** @var HumanReadablePropertyAccessorFactory */
    protected $accessorFactory;

    /** currently accessed object */
    protected $object;

    /**
     * @param $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * @param mixed $accessorFactory
     */
    public function setAccessorFactory($accessorFactory)
    {
        $this->accessorFactory = $accessorFactory;
    }

    /**
     * @param $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return true;
//        $accessor = new PropertyAccessor();

//        $property = $this->getRealPropertyName($property);
//        return $property ? $accessor->isReadable($this->object, $property) : false;
    }

    /**
     * Magic getter for property path.
     *
     * @param $property string on the form 'myObjectAlias.MyPropertyAlias'
     *
     * @return mixed
     */
    public function __get($property)
    {
        try {
            $accessor = new PropertyAccessor();
            list($rootProperty, $otherProperties) = $this->firstPropertyLevels($property);
            $rootProperty = $this->getRealPropertyName($rootProperty) ?: $rootProperty;
            $value = $rootProperty ? $accessor->getValue($this->object, $rootProperty) : 'Propriété non défini';
        } catch (NoSuchPropertyException $e) {
            return 'Propriété non définie';
        }
            // if property is empty like category.name but category is null
        catch (UnexpectedTypeException $e) {
            return null;
        }

        if (is_object($value) && $this->accessorFactory->hasEntry(get_class($value))) {
            if (!empty($otherProperties)) {
                return $this->continueAccessProperties($otherProperties, $value);
            }

            return $this->accessorFactory->getAccessor($value);
        } elseif ($value instanceof \Traversable) {
            $array = array();
            foreach ($value as $val) {
                $array[] = $this->accessorFactory->getAccessor($val);
            }

            return $array;
        }

        // reformat the scalar property if needed
        return $this->format($property, $value);
    }

    /**
     * Return an array of accessors from the object properties.
     *
     * @return array
     */
    public function toArray()
    {
        $catalog = $this->accessorFactory->getTermCatalog(get_class($this->object));
        $return = array();

        foreach ($catalog['fields'] as $name => $options) {
            if ((is_object($this->$name)) && $this->accessorFactory->hasEntry(get_class($this->$name))) {
                $return[$name] = $this->accessorFactory->getAccessor($this->$name)->toArray();
            } elseif (is_object($this->$name) && get_class($this->$name) === get_class($this)) {
                $return[$name] = $this->$name->toArray();
            } elseif (empty($this->$name)) {
                $return[$name] = array();
            } else {
                $return[$name] = $this->$name;
            }
        }

        return $return;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->object;
    }

    /**
     * Return first level like stagiaire for stagiaire.nom.
     *
     * @param $property
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function firstPropertyLevels($property)
    {
        $expl = explode('.', $property);
        if (count($expl) === 1) {
            $rootProperty = $property;
            $otherProperties = '';
        } else {
            // make work stagiaire.nom instead of inscription.stagiaire.nom if object is an inscription for i.e.
            if ($expl[0] === $this->accessorFactory->getEntityAlias(get_class($this->object))) {
                $expls = array();
                foreach ($expl as $key => $value) {
                    if (isset($expl[$key + 1])) {
                        $expls[$key] = $expl[$key + 1];
                    }
                }
                $expl = $expls;
            }
            $rootProperty = $expl[0];
            $otherProperties = implode('.', array_slice($expl, 1));
        }

        return array($rootProperty, $otherProperties);
    }

    /**
     * @param string $otherProperties
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function continueAccessProperties($otherProperties, $value)
    {
        if (is_object($value) && $this->accessorFactory->hasEntry(get_class($value))) {
            /** @var HumanReadablePropertyAccessor $nextAccessor */
            $nextAccessor = $this->accessorFactory->getAccessor($value);
            if ($nextAccessor) {
                try {
                    return $nextAccessor->$otherProperties;
                } catch (\Exception $e) {
                    return 'Non défini';
                }
            }
        }

        return null;
    }

    /**
     * Get the PHP property name instead of the human readable alias (ie: nom => name).
     *
     * @param $property
     *
     * @return null|string
     */
    protected function getRealPropertyName($property)
    {
        if ($property === 'email') {
            $property = $this->accessorFactory->getMailPath(get_class($this->object)) ?: $property;
        }

        return $this->accessProperty($property);
    }

    /**
     * returns a formatted version of requested value. useful for date for the moment.
     *
     * @param $prefix
     * @param $value
     *
     * @return mixed
     */
    protected function format($prefix, $value)
    {
        $format = $this->accessorFactory->getFormatForAlias(get_class($this->object), $prefix);
        $type = $this->accessorFactory->getTypeForAlias(get_class($this->object), $prefix);
        if ($value instanceof \DateTime) {
            if ($format) {
                /* @var \DateTime $value */
                return $value->format($format);
            } else {
                return $value->format('d/m/Y');
            }
        } elseif (is_bool($value)) {
            return $value ? 'oui' : 'non';
        } elseif (is_string($value) && $type === 'ckeditor') {
            return Html2Text::convert($value);
        }

        return $value;
    }

    /**
     * Get real propery name from catalog.
     *
     * @param $property
     *
     * @return null|string
     */
    protected function accessProperty($property)
    {
        return $this->accessorFactory->getPropertyForAlias(get_class($this->object), $property);
    }
}
