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

class Value
{
    protected $value;
    protected $func;
    protected $type;

    public function __construct(Func $func, $arg1, $arg2 = null)
    {
        $this->func = $func;
        if (!is_null($arg2)) {
            $this->value = $arg1;
            $this->type = $arg2;
        } else {
            $this->type = $arg1;
        }
        if (!$this->type instanceof Type) {
            throw new \InvalidArgumentException("Expecting a type");
        }
    }

    public function isTemporary()
    {
        return false;
    }

    public function isLocal()
    {
        return true;
    }

    public function isConstant()
    {
        return !is_null($this->value);
    }

    public function isParamter()
    {
        return false;
    }

    public function isVolatile()
    {
        return true;
    }

    public function isAddressable()
    {
        return false;
    }

    public function isTrue()
    {
        return (bool) $this->value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFunction()
    {
        return $this->func;
    }

    public function dump($prefix = null)
    {
        echo $prefix;
        $this->type->dump();
        echo $this->value;
    }

}
