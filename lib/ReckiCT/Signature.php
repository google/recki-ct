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
 * @package Main
 */

namespace ReckiCT;

/**
 * Represents a function signature
 *
 * This class holds the type signatures for functions.
 *
 * @api
 */
class Signature
{
    /**
     * @var \ReckiCT\Type The return type of the function
     */
    protected $return;

    /**
     * @var \ReckiCT\Type[] The types of each parameter
     */
    protected $params = [];

    /**
     * Construct the signature
     *
     * @param \ReckiCT\Type   $return The return type of the function
     * @param \ReckiCT\Type[] $params The types of the parameters to the function
     */
    public function __construct(Type $return, array $params)
    {
        $this->return = $return;
        $this->params = $params;
    }

    /**
     * Get the return type of the function
     *
     * @return \ReckiCT\Type The return type
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * Get the type of an arbitrary parameter to the function
     *
     * Since PHP supports varargs for all functions, this will always return
     * TYPE_UNKNOWN if the param isn't known
     *
     * @param int $key The parameter number to fetch (0-indexed)
     *
     * @return \ReckiCT\Type The parameter's type
     */
    public function getParam($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : new Type(Type::TYPE_UNKNOWN);
    }

    /**
     * Get all known parameter types for the function
     *
     * @return \ReckiCT\Type[] Types of all known parameters
     */
    public function getParams()
    {
        return $this->params;
    }

}
