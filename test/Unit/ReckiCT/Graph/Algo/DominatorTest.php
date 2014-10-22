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
 * @package Graph
 * @subpackage Algo
 */

namespace ReckiCT\Graph\Algo;

use PHPUnit_Framework_TestCase as TestCase;

use ReckiCT\Type;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;
use Gliph\Graph\DirectedAdjacencyList;

/**
 * @coversDefaultClass \ReckiCT\Graph\Algo\Dominator
 */
class DominatorTest extends TestCase
{
    protected $func;
    protected $graph;
    protected $verticies = [];

    protected function setUp()
    {
        $this->graph = new DirectedAdjacencyList();
        $this->func = new JitFunction([], new Type(0), $this->graph);
        $this->v = [
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
        ];
        $this->graph->ensureArc($this->func, $this->v[0]);
        //
        //         2
        // 0 - 1 <   > 4 - 5
        //         3
        //
        $this->graph->ensureArc($this->v[0], $this->v[1]);
        $this->graph->ensureArc($this->v[1], $this->v[2]);
        $this->graph->ensureArc($this->v[1], $this->v[3]);
        $this->graph->ensureArc($this->v[2], $this->v[4]);
        $this->graph->ensureArc($this->v[3], $this->v[4]);
        $this->graph->ensureArc($this->v[4], $this->v[5]);
    }

    /**
     * @covers ::__construct
     * @covers ::build
     * @covers ::reduce
     * @covers ::dominates
     */
    public function testDominatorWithNonExistantVertex()
    {
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertFalse($dominator->dominates($this->getMock(Vertex::class), $this->v[1]));
    }

    /**
     * @covers ::__construct
     * @covers ::build
     * @covers ::reduce
     * @covers ::dominates
     */
    public function testDominator()
    {
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertTrue($dominator->dominates($this->v[0], $this->v[0]));
        $this->assertTrue($dominator->dominates($this->v[1], $this->v[0]));
        $this->assertFalse($dominator->dominates($this->v[4], $this->v[2]));
        $this->assertTrue($dominator->dominates($this->v[4], $this->v[1]));
    }

    /**
     * @depends testDominator
     * @covers ::strictlyDominates
     */
    public function testStrictlyDominates()
    {
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertFalse($dominator->strictlyDominates($this->v[0], $this->v[0]));
        $this->assertTrue($dominator->strictlyDominates($this->v[1], $this->v[0]));
        $this->assertTrue($dominator->strictlyDominates($this->v[4], $this->v[1]));
    }

    /**
     * @depends testStrictlyDominates
     * @covers ::immediateDominator
     */
    public function testImmediateDominator()
    {
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertNull($dominator->immediateDominator($this->func)); // A function doesn't have an idom
        $this->assertSame($this->func, $dominator->immediateDominator($this->v[0]));
        $this->assertSame($this->v[4], $dominator->immediateDominator($this->v[5]));
        $this->assertSame($this->v[1], $dominator->immediateDominator($this->v[4]));
    }

    /**
     * @covers ::immediateDominatorArray
     */
    public function testImmediateDominatorArray()
    {
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertSame($this->v[1], $dominator->immediateDominatorArray([$this->v[2], $this->v[3]]));
        $this->assertSame($this->v[1], $dominator->immediateDominatorArray([$this->v[2], $this->v[4]]));
        $this->assertSame($this->v[0], $dominator->immediateDominatorArray([$this->v[1], $this->v[4]]));
    }

    /**
     * @depends testImmediateDominatorArray
     * @covers ::immediateDominatorArray
     *
     *       /---\
     *      /  2  \
     * 0 - 1 <   > 4 - 5    
     *         3
     */
    public function testImmediateDominatorArrayCycles()
    {
        $this->graph->ensureArc($this->v[4], $this->v[1]);
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertSame($this->v[1], $dominator->immediateDominatorArray([$this->v[2], $this->v[3]]));
    }

    /**
     * @depends testStrictlyDominates
     * @covers ::getFrontier
     *
     *     ------
     * 0 <     2 \
     *     1 <   > 4 - 5
     *         3
     */
    public function testFrontier()
    {
        $this->graph->ensureArc($this->v[0], $this->v[4]);
        $dominator = new Dominator($this->graph, $this->func);
        $this->assertSame([], $dominator->getFrontier($this->v[0]));
        $this->assertSame([$this->v[4]], $dominator->getFrontier($this->v[1]));
        $this->assertSame([$this->v[4]], $dominator->getFrontier($this->v[2]));
        $this->assertSame([$this->v[4]], $dominator->getFrontier($this->v[3]));
        $this->assertSame([], $dominator->getFrontier($this->v[4]));
    }

    /**
     * @depends testStrictlyDominates
     * @covers ::getFrontier
     *
     *     ------
     * 0 <     2 \
     *     1 <   > 4 - 5
     *         3
     */
    public function testFrontierMutatedGraph()
    {
        $this->graph->ensureArc($this->v[0], $this->v[4]);
        $dominator = new Dominator($this->graph, $this->func);
        $this->graph->ensureArc($this->v[0], $this->getMock(Vertex::class));
        $this->assertSame([], $dominator->getFrontier($this->v[0]));
        $this->assertSame([$this->v[4]], $dominator->getFrontier($this->v[1]));
        $this->assertSame([$this->v[4]], $dominator->getFrontier($this->v[2]));
        $this->assertSame([$this->v[4]], $dominator->getFrontier($this->v[3]));
        $this->assertSame([], $dominator->getFrontier($this->v[4]));
    }
}
