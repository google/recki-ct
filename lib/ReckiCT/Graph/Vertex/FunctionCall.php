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
use ReckiCT\Graph\Variable;
use ReckiCT\Signature;

class FunctionCall extends Assign
{
    use Traits\Arguments;

    protected $fname;
    protected $isSelfRecursive = false;
    protected $isIdempotent = false;
    protected $signature;

    public function __construct($fname, array $args, Variable $result, Signature $signature, $isSelfRecursive = false)
    {
        $this->fname = $fname;
        $this->args = $args;
        $this->setResult($result);
        $this->isSelfRecursive = (bool) $isSelfRecursive;
        $this->signature = $signature;
    }

    public function __toString()
    {
        return $this->result . ' = ' . $this->fname . '(' . implode(', ', $this->args) . ')';
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function getFunctionName()
    {
        return $this->fname;
    }

    public function isIdempotent()
    {
        return $this->isIdempotent;
    }

    public function isSelfRecursive()
    {
        return $this->isSelfRecursive;
    }

    public function getVariables()
    {
        return array_merge($this->args, [$this->result]);
    }

    public function replaceVariable(Variable $from, Variable $to)
    {
        foreach ($this->args as $key => $arg) {
            if ($arg === $from) {
                $this->args[$key] = $to;
            }
        }
    }

}
