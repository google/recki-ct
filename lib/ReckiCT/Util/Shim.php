<?php
/**
 * Copyright 2014 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 Google Inc. All rights reserved
 * @license http://www.apache.org/licenses/LICENSE-2.0.txt Apache-2.0
 * @package Util
 */
namespace ReckiCT\Util;

/**
 * These are shims for missing or poorly implemented core functionality
 *
 * This works around issues in PHP's core libraries
 *
 * @internal
 */
class Shim
{
    /**
     * Find the intersection of 2 arrays based on strict equality
     *
     * This works around a limitation of the core `array_intersect` function
     * which compares elements by casting elements to strings
     *
     * @param array $left  The first array
     * @param array $right The second array
     *
     * @return array The intersection of the two source arrays
     */
    public static function array_intersect(array $left, array $right)
    {
        $result = [];
        foreach ($left as $value) {
            foreach ($right as $value2) {
                if ($value === $value2) {
                    $result[] = $value;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Return an array containing only unique elements from the source array
     *
     * This works around a limitation of the core `array_unique` function
     * which compares elements by casting them to strings
     *
     * @param array $array The array to operate on
     *
     * @return array A new array containing only unique elements of $array
     */
    public static function array_unique(array $array)
    {
        $found = array();
        $result = array();
        foreach ($array as $value) {
            $key = self::getKey($value);
            if (!isset($found[$key])) {
                $result[] = $value;
                $found[$key] = true;
            }
        }

        return $result;
    }

    protected static function getKey($arg)
    {
        if (is_object($arg)) {
            return spl_object_hash($arg);
        }

        return 'key_' . $arg;
    }

}
