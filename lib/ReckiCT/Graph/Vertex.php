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
 * @package Graph
 */

namespace ReckiCT\Graph;

abstract class Vertex
{
    const IMPORTANT_MAYBE = -1;
    const IMPORTANT_NOT = 0;
    const IMPORTANT = 1;
    const IMPORTANT_CRITICAL = 2;
    const IMPORTANT_END = -3;

    public $important = self::IMPORTANT_NOT;

    abstract public function __toString();

    public function getName()
    {
        return str_replace('_', '', substr(strrchr(get_class($this), '\\'), 1));
    }

    public function hasVariable(Variable $var)
    {
        foreach ($this->getVariables() as $test) {
            if ($test === $var) {
                return true;
            }
        }

        return false;
    }

    abstract public function getVariables();

    abstract public function replaceVariable(Variable $from, Variable $to);

}
