<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:16.
 */

namespace App\BatchOperations;

interface BatchOperationInterface
{
    /**
     * @param $id
     *
     * @return mixed
     */
    public function setId($id): mixed;

    /**
     * @return mixed
     */
    public function getId(): mixed;

    /**
     *
     * @return mixed
     */
    public function execute(array $idList = [], array $options = []): mixed;

    /**
     * @return array modal window modal config options
     */
    public function getModalConfig($options = []): array;
}
