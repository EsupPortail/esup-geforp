<?php

namespace App\Utils;

/**
 * Class ArrayFunctions
 * @package Sygefor\Bundle\CoreBundle\Utils
 */
class ArrayFunctions
{
    /**
     * Walks through given array and replaces every found empty array
     * by an empty string.
     * The PHP native function `array_walk_recursive` wouldn't do the job
     * since it ignores empty arrays by nature.
     *
     * Example:
     *   Input:  [0 => [], 1 => "Hey", 2 => ["banana" => "apple" ]]
     *   Output: [0 => "", 1 => "Hey", 2 => ["banana" => "apple"]]
     * First value has been replaced.
     *
     * Note that directly passing an empty array will return the same
     * empty array; this method will never return a string.
     * Example
     *   Input:  []
     *   Output: []
     *
     * @param array $values
     *
     * @return array
     */
    public static function emptyArraysToStringsRecursive(array $values)
    {
        array_walk($values, function (&$item, $key) {
            if (is_array($item)) {
                if (empty($item)) {
                    $item = '';
                } else {
                    $item = self::emptyArraysToStringsRecursive($item);
                }
            }
        });

        return $values;
    }
}