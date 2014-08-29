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
 * @category Tests
 * @package Analyzer
 */

namespace ReckiCT\Analyzer;

use PHPUnit_Framework_TestCase as TestCase;

use ReckiCT\Graph\Vertex\End;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;
use ReckiCT\Type;
use Gliph\Graph\DirectedAdjacencyList;

use ReckiCT\Graph\Algo\Dominator;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\GraphState
 */
class GraphStateTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getGraph
     * @covers ::getFunction
     * @covers ::getDominator
     * @covers ::getPostDominator
     * @covers ::getInverseGraph
     */
    public function testBasicUsage()
    {
        $func = new JitFunction([], new Type(0), $graph = new DirectedAdjacencyList());
        $graph->addDirectedEdge($func, new End());
        $state = new GraphState($func);
        $this->assertSame($func, $state->getFunction());
        $this->assertSame($graph, $state->getGraph());
        $this->assertInstanceOf(Dominator::class, $state->getDominator());
        $this->assertInstanceOf(Dominator::class, $state->getPostDominator());
        $this->assertInstanceOf(DirectedAdjacencyList::class, $state->getInverseGraph());
    }

}
