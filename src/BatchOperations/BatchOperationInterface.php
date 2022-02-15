<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:16.
 */

namespace CoreBundle\BatchOperations;

interface BatchOperationInterface
{
    /**
     * @param $id
     *
     * @return mixed
     */
    public function setId($id);

    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array());

    /**
     * @return array modal window modal config options
     */
    public function getModalConfig($options = array());
}
