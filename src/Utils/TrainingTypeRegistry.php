<?php

namespace App\Utils;

final class TrainingTypeRegistry
{
    /**
     * @param $types
     * @param mixed[] $types
     */
    public function __construct(private $types)
    {
    }

    /**
     * @param array $types
     */
    public function setTypes($types): void
    {
        $this->types = $types;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param $type
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function getType($type)
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException('Invalid training type : '.$type);
        }

        return $this->types[$type];
    }
}
