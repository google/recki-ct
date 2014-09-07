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
 */

namespace ReckiCT\Graph;

use PHPUnit_Framework_TestCase as TestCase;

use Gliph\Graph\DirectedAdjacencyList;

use ReckiCT\Graph\Vertex\End as JitEnd;
use ReckiCT\Graph\Vertex\Phi;

/**
 * @coversDefaultClass \ReckiCT\Graph\Helper
 */
class HelperTest extends TestCase
{
    protected $graph;
    protected $verticies = [];

    protected function setUp()
    {
        $r = new \ReflectionProperty(Helper::class, 'processing');
        $r->setAccessible(true);
        $r->setValue(null);
        $this->graph = new DirectedAdjacencyList();
        $this->v = [
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
        ];
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
     * @covers ::computeImmediatePredecessors
     * @covers ::findAllSuccessors
     */
    public function testComputeImmediatePredecessors()
    {
        $pred = Helper::computeImmediatePredecessors($this->graph);
        $this->assertSame([], $pred[$this->v[0]]);
        $this->assertSame([$this->v[0]], $pred[$this->v[1]]);
        $this->assertSame([$this->v[1]], $pred[$this->v[2]]);
        $this->assertSame([$this->v[1]], $pred[$this->v[3]]);
        $this->assertSame([$this->v[2], $this->v[3]], $pred[$this->v[4]]);
        $this->assertSame([$this->v[4]], $pred[$this->v[5]]);
    }

    /**
     * @covers ::computeImmediatePredecessors
     * @covers ::findAllSuccessors
     */
    public function testComputeImmediatePredecessorsWithCycle()
    {
        $this->graph->ensureArc($this->v[4], $this->v[1]);
        $pred = Helper::computeImmediatePredecessors($this->graph);
        $this->assertSame([], $pred[$this->v[0]]);
        $this->assertSame([$this->v[0], $this->v[4]], $pred[$this->v[1]]);
        $this->assertSame([$this->v[1]], $pred[$this->v[2]]);
        $this->assertSame([$this->v[1]], $pred[$this->v[3]]);
        $this->assertSame([$this->v[2], $this->v[3]], $pred[$this->v[4]]);
        $this->assertSame([$this->v[4]], $pred[$this->v[5]]);
    }

    /**
     * @covers ::computePredecessors
     * @covers ::findAllSuccessors
     */
    public function testComputePredecessors()
    {
        $pred = Helper::computePredecessors($this->graph);
        $this->assertSame([], $pred[$this->v[0]]);
        $this->assertSame([$this->v[0]], $pred[$this->v[1]]);
        $this->assertSame([$this->v[1], $this->v[0]], $pred[$this->v[2]]);
        $this->assertSame([$this->v[1], $this->v[0]], $pred[$this->v[3]]);
        $this->assertSame([$this->v[2], $this->v[1], $this->v[0], $this->v[3]], $pred[$this->v[4]]);
        $this->assertSame([$this->v[4], $this->v[2], $this->v[1], $this->v[0], $this->v[3]], $pred[$this->v[5]]);
    }

    /**
     * @covers ::computePredecessors
     * @covers ::findAllSuccessors
     */
    public function testComputePredecessorsWithCycle()
    {
        $this->graph->ensureArc($this->v[4], $this->v[1]);
        $pred = Helper::computePredecessors($this->graph);
        $this->assertSame([$this->v[0], $this->v[4], $this->v[2], $this->v[1], $this->v[3]], $pred[$this->v[1]]);
    }

    /**
     * @covers ::insertAfter
     */
    public function testInsertAfterSingle()
    {
        Helper::insertAfter($this->v[0], $this->v[6], $this->graph);

        $adj = $this->getValues($this->graph->successorsOf($this->v[0]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[6], $adj[0]);

        $adj = $this->getValues($this->graph->successorsOf($this->v[6]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[1], $adj[0]);
    }

    /**
     * @covers ::insertAfter
     */
    public function testInsertAfterDouble()
    {
        Helper::insertAfter($this->v[1], $this->v[6], $this->graph);

        $adj = $this->getValues($this->graph->successorsOf($this->v[1]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[6], $adj[0]);

        $adj = $this->getValues($this->graph->successorsOf($this->v[6]));
        $this->assertEquals(2, count($adj));
        $this->assertSame($this->v[2], $adj[0]);
        $this->assertSame($this->v[3], $adj[1]);
    }

    /**
     * @covers ::replace
     */
    public function testReplaceMultipleOutbound()
    {
        Helper::replace($this->v[1], $this->v[6], $this->graph);
        $this->assertFalse($this->graph->hasVertex($this->v[1]));

        $adj = $this->getValues($this->graph->successorsOf($this->v[6]));
        $this->assertEquals(2, count($adj));
        $this->assertSame($this->v[2], $adj[0]);
        $this->assertSame($this->v[3], $adj[1]);
    }

    /**
     * @covers ::replace
     */
    public function testReplaceMultipleInbound()
    {
        Helper::replace($this->v[4], $this->v[6], $this->graph);
        $this->assertFalse($this->graph->hasVertex($this->v[4]));

        $adj = $this->getValues($this->graph->successorsOf($this->v[2]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[6], $adj[0]);

        $adj = $this->getValues($this->graph->successorsOf($this->v[3]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[6], $adj[0]);

        $adj = $this->getValues($this->graph->successorsOf($this->v[6]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[5], $adj[0]);
    }

    /**
     * @covers ::remove
     */
    public function testRemoveMultipleOutbound()
    {
        Helper::remove($this->v[1], $this->graph);
        $this->assertFalse($this->graph->hasVertex($this->v[1]));

        $adj = $this->getValues($this->graph->successorsOf($this->v[0]));
        $this->assertEquals(2, count($adj));
        $this->assertSame($this->v[2], $adj[0]);
        $this->assertSame($this->v[3], $adj[1]);
    }

    /**
     * @covers ::remove
     */
    public function testRemoveMultipleInbound()
    {
        Helper::remove($this->v[4], $this->graph);
        $this->assertFalse($this->graph->hasVertex($this->v[4]));

        $adj = $this->getValues($this->graph->successorsOf($this->v[2]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[5], $adj[0]);

        $adj = $this->getValues($this->graph->successorsOf($this->v[3]));
        $this->assertEquals(1, count($adj));
        $this->assertSame($this->v[5], $adj[0]);
    }

    /**
     * @covers ::getInboundNodes
     */
    public function testGetInboundNodesNoInbound()
    {
        $in = $this->getValues(Helper::getInboundNodes($this->v[0], $this->graph));
        $this->assertEmpty($in);
    }

    /**
     * @covers ::getInboundNodes
     */
    public function testGetInboundNodesWithInbound()
    {
        $in = $this->getValues(Helper::getInboundNodes($this->v[4], $this->graph));
        $this->assertEquals(2, count($in));
        $this->assertSame($this->v[2], $in[0]);
        $this->assertSame($this->v[3], $in[1]);
    }

    /**
     * @covers ::findVariables
     */
    public function testGetVariables()
    {
        $vars = [];
        for ($i = 0; $i <= 5; $i++) {
            $var = new Variable();
            $this->v[$i]->expects($this->once())
                ->method('getVariables')
                ->will($this->returnValue([$var]));
            $vars[] = $var;
        }
        $this->assertSame($vars, Helper::findVariables($this->graph));
    }

    /**
     * @covers ::isPhiVar
     */
    public function testisPhiVarWithoutPhiNodes()
    {
        $this->assertFalse(Helper::isPhiVar(new Variable(), $this->graph));
    }

    /**
     * @covers ::isPhiVar
     */
    public function testisPhiVarResult()
    {
        $this->graph->ensureArc($this->v[5], new Phi($r = new Variable()));
        $this->assertTrue(Helper::isPhiVar($r, $this->graph));
    }

    /**
     * @covers ::isPhiVar
     */
    public function testisPhiVarValue()
    {
        $this->graph->ensureArc($this->v[5], $p = new Phi(new Variable()));
        $p->addValue($a = new Variable());
        $this->assertTrue(Helper::isPhiVar($a, $this->graph));
    }

    /**
     * @covers ::isPhiVar
     */
    public function testisPhiVarNonValue()
    {
        $this->graph->ensureArc($this->v[5], $p = new Phi(new Variable()));
        $p->addValue(new Variable());
        $this->assertFalse(Helper::isPhiVar(new Variable(), $this->graph));
    }

    /**
     * @covers ::findVerticesByClass
     */
    public function testFindVerticiesByClass()
    {
        $end = new JitEnd();
        $this->graph->ensureArc($this->v[5], $end);
        $this->assertSame([$end], Helper::findVerticesByClass(JitEnd::class, $this->graph));
    }

    /**
     * @covers ::isLiveVar
     */
    public function testIsLiveVar()
    {
        $graph = new DirectedAdjacencyList();
        $var = new Variable();
        $vertex = $this->getMock(Vertex::class);
        $graph->ensureVertex($vertex);
        $vertex->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([$var]));
        $this->assertTrue(Helper::isLiveVar($var, $vertex, $graph));
    }

    /**
     * @covers ::isLiveVar
     */
    public function testIsLiveVarWithCycle()
    {
        $graph = new DirectedAdjacencyList();
        $var = new Variable();
        $vertex = $this->getMock(Vertex::class);
        $graph->ensureVertex($vertex);
        $vertex->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([]));

        $v1 = $this->getMock(Vertex::class);
        $v1->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([]));
        $graph->ensureArc($vertex, $v1);
        $graph->ensureArc($v1, $vertex);
        $this->assertFalse(Helper::isLiveVar($var, $vertex, $graph));
    }

    /**
     * @covers ::isLiveVar
     */
    public function testIsLiveVarWithCycleAndLaterValue()
    {
        $graph = new DirectedAdjacencyList();
        $var = new Variable();
        $vertex = $this->getMock(Vertex::class);
        $graph->ensureVertex($vertex);
        $vertex->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([]));

        $v1 = $this->getMock(Vertex::class);
        $v1->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([$var]));
        $graph->ensureArc($vertex, $v1);

        $this->assertTrue(Helper::isLiveVar($var, $vertex, $graph));
    }

    protected function getValues(\Generator $gen)
    {
        $values = [];
        foreach ($gen as $value) {
            $values[] = $value;
        }

        return $values;
    }
}
