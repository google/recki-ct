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
 * @package Stubs
 */

namespace JITFU;

class Type
{
    const double = 1;
    const long = 2;
    const string = 3;
    const void = 4;
    const zval = 5;

    protected static $types = array();
    protected static $counter = 1;

    protected $id;
    protected $type;
    protected $isPointer;

    protected function __construct($type, $isPointer = false)
    {
        $this->type = $type;
        $this->isPointer = (bool) $isPointer;
        $this->id = self::$counter++;
    }

    public static function of($name)
    {
        if (!isset(self::$types[$name])) {
            self::$types[$name] = new Type($name);
        }

        return self::$types[$name];
    }

    public function getIdentifier()
    {
        return $this->id;
    }

    public function getIndirection()
    {
        if ($this->isPointer) {
            if ($this->type instanceof Type) {
                return 1 + $this->type->getIndirection();
            }

            return 1;
        }

        return 0;
    }

    public function isPointer()
    {
        return $this->isPointer;
    }

    public function getType()
    {
        return $this->type;
    }

    public function dump()
    {
        echo $this->type;
    }

}
