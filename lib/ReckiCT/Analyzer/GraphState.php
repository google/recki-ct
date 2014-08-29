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
 * @package Analyzer
 */

namespace ReckiCT\Analyzer;

use ReckiCT\Graph\Algo\Dominator;

use ReckiCT\Graph\Helper;
use ReckiCT\Graph\Vertex\End;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;

class GraphState
{
    protected $function;
    protected $graph;

    public function __construct(JitFunction $func)
    {
        $this->function = $func;
        $this->graph = $func->getGraph();
    }

    public function getDominator()
    {
        return new Dominator($this->graph, $this->function);
    }

    public function getPostDominator()
    {
        return new Dominator($this->getInverseGraph(), Helper::findVerticesByClass(End::class, $this->graph)[0]);
    }

    public function getFunction()
    {
        return $this->function;
    }

    public function getGraph()
    {
        return $this->graph;
    }

    public function getInverseGraph()
    {
        return $this->graph->transpose();
    }

}
