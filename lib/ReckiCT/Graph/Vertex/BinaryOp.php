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
 * @subpackage Vertex
 */

namespace ReckiCT\Graph\Vertex;

use ReckiCT\Graph\Traits;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Assignment;
use ReckiCT\Graph\Variable;

class BinaryOp extends Vertex implements Assignment
{
    use Traits\Assignment;

    const CONCAT = '.';

    const PLUS = '+';
    const MINUS = '-';
    const MUL = '*';
    const DIV = '/';
    const MOD = '%';

    const EQUAL = '==';
    const NOT_EQUAL = '!=';
    const IDENTICAL = '===';
    const NOT_IDENTICAL = '!==';

    const GREATER = '>';
    const GREATER_EQUAL = '>=';
    const SMALLER = '<';
    const SMALLER_EQUAL = '<=';

    const BITWISE_AND = '&';
    const BITWISE_OR = '|';
    const BITWISE_XOR = '^';
    const SHIFT_LEFT = '<<';
    const SHIFT_RIGHT = '>>';

    protected $kind = '';
    protected $a;
    protected $b;

    public function __construct($kind, Variable $a, Variable $b, Variable $result)
    {
        $this->kind = $kind;
        $this->a = $a;
        $this->b = $b;
        $this->setResult($result);
    }

    public function __toString()
    {
        return $this->result . ' = ' . $this->a . ' ' . $this->kind . ' ' . $this->b;
    }

    public function getA()
    {
        return $this->a;
    }

    public function getB()
    {
        return $this->b;
    }

    public function getKind()
    {
        return $this->kind;
    }

    public function getVariables()
    {
        return [$this->a, $this->b, $this->result];
    }

    public function replaceVariable(Variable $from, Variable $to)
    {
        if ($this->a === $from) {
            $this->a = $to;
        }
        if ($this->b === $from) {
            $this->b = $to;
        }
    }

}
