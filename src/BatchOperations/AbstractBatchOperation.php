<?php

namespace App\BatchOperations;

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
    private string $id;

    /**
     * @var string
     */
    private string $label;

    /**
     * @var string
     */
    protected string $targetClass;

    /**
     * @var ManagerRegistry
     */
    protected ManagerRegistry $doctrine;

    /**
     * @var array
     */
    protected array $options;

    public function __construct(){
        $this->options = [];
        $this->label = "";
    }
    /**
     * @param $id
     */
    public function setId($id): mixed
    {
       return $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $class
     */
    public function setTargetClass(string $class): void
    {
        $this->targetClass = $class;
    }

    /**
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * @var string
     */
    public function setLabel($label): void
    {
        $this->label = $label;
    }

    /**
     * the label for operation (will be displayed in available operations list).
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    public function setDoctrine(ManagerRegistry $managerRegistry): void
    {
        $this->doctrine = $managerRegistry;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
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
    protected function getObjectList($idList): array
    {
//        $entities = $this->em->getRepository($this->targetClass)->findBy(array('id' => $idList));
        $entities = $this->doctrine->getRepository($this->targetClass)->findBy(['id' => $idList]);
        $this->reorderByKeys($entities, $idList);

        return $entities;
    }

    /**
     * @return array modal window modal config options
     */
    public function getModalConfig($options = []): array
    {
        return [];
    }

    /**
     * Re-order a list by keys.
     */
    protected function reorderByKeys(&$items, $keys): void
    {
        usort($items, static function ($a, $b) use ($keys) : int {
            $position_a = array_search($a->getId(), $keys, true);
            $position_b = array_search($b->getId(), $keys, true);
            return  $position_a < $position_b ? -1 : 1;
        });
    }
}
