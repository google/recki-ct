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
use ReckiCT\Graph\Variable;
use ReckiCT\Type;

use Gliph\Graph\Digraph;

class Function_ extends Vertex
{
    use Traits\Arguments;

    protected $type;
    protected $graph;

    public function __construct(array $args, Type $type, Digraph $graph)
    {
        $this->args = $args;
        $this->type = $type;
        $this->graph = $graph;
    }

    public function __toString()
    {
        return 'function(' . implode(', ', $this->args) . '): ' . $this->type;
    }

    public function getGraph()
    {
        return $this->graph;
    }

    public function getReturnType()
    {
        return $this->type;
    }

    public function getVariables()
    {
        return $this->args;
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
