<?php

namespace App\BatchOperations;

use App\Entity\Core\AbstractInscription;
use App\Entity\Inscription;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AbstractBatchOperation.
 */
abstract class AbstractBatchOperation implements BatchOperationInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    protected $targetClass;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $class
     */
    public function setTargetClass($class)
    {
        $this->targetClass = $class;
    }

    /**
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }

    /**
     * @var string
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * the label for operation (will be displayed in available operations list).
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param ManagerRegistry $doctrine
     */
    public function setDoctrine(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Return entity array with id list.
     *
     * @param $idList
     *
     * @return array
     */
    protected function getObjectList($idList)
    {
//        $entities = $this->em->getRepository($this->targetClass)->findBy(array('id' => $idList));
        $entities = $this->doctrine->getRepository($this->targetClass)->findBy(array('id' => $idList));
        $this->reorderByKeys($entities, $idList);

        return $entities;
    }

    /**
     * @return array modal window modal config options
     */
    public function getModalConfig($options = array())
    {
        return array();
    }

    /**
     * Re-order a list by keys.
     */
    protected function reorderByKeys(&$items, $keys)
    {
        usort($items, function ($a, $b) use ($keys) {
            $position_a = array_search($a->getId(), $keys);
            $position_b = array_search($b->getId(), $keys);

            return  $position_a < $position_b ? -1 : 1;
        });
    }
}
