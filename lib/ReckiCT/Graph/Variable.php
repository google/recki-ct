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

use ReckiCT\Type;

class Variable
{
    protected static $ctr = 0;
    protected $num = 0;
    protected $type;

    public function __construct(Type $type = null)
    {
        $this->num = ++self::$ctr;
        if ($type) {
            $this->type = $type;
        } else {
            $this->type = new Type(Type::TYPE_UNKNOWN);
        }
    }

    public function __toString()
    {
        return $this->type . '_' . $this->num;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function isConstant()
    {
        return false;
    }
}
