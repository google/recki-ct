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

class Constant extends Variable
{
    protected $value;

    public function __construct($value)
    {
        if (is_int($value) || $value === (double) (int) $value) {
            parent::__construct(new Type(Type::TYPE_NUMERIC));
        } elseif (is_float($value)) {
            parent::__construct(new Type(Type::TYPE_DOUBLE));
        } elseif (is_string($value)) {
            parent::__construct(new Type(Type::TYPE_STRING));
        } elseif (is_bool($value)) {
            parent::__construct(new Type(Type::TYPE_BOOLEAN));
        } else {
            throw new \RuntimeException("Invalid constant type encountered: " . gettype($value));
        }
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->type . '(' . $this->value . ')';
    }

    public function getValue()
    {
        return $this->value;
    }
}
