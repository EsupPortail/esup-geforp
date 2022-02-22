<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AbstractController.
 */
abstract class ApiAbstractController extends AbstractController
{
    /**
     * @var array
     */
    protected static $authorizedFields = array();

    /**
     * Protected function to help build authorized fields array.
     *
     * @param $source
     * @param string $prefix
     *
     * @return array
     */
    protected static function buildAuthorizedFieldsArray($source, $prefix = '')
    {
        $array = array();
        foreach (static::$authorizedFields[$source] as $key) {
            $array[] = ($prefix ? $prefix.'.' : '').$key;
        }

        return $array;
    }
}
