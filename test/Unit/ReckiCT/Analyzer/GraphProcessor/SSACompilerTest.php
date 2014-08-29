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
 * @subpackage GraphProcessor
 */

namespace ReckiCT\Analyzer\GraphProcessor;

use PHPUnit_Framework_TestCase as TestCase;

use Gliph\Graph\DirectedAdjacencyList;

use ReckiCT\Analyzer\GraphState;

use ReckiCT\Type;

use ReckiCT\Graph\Algo\Dominator;
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Constant;

use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Vertex\End;
use ReckiCT\Graph\Vertex\Phi;
use ReckiCT\Graph\Vertex\Jump;
use ReckiCT\Graph\Vertex\NoOp;
use ReckiCT\Graph\Vertex\JumpZ;
use ReckiCT\Graph\Vertex\Assign;
use ReckiCT\Graph\Vertex\Return_;
use ReckiCT\Graph\Vertex\BinaryOp;
use ReckiCT\Graph\Vertex\Function_;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\GraphProcessor\SSACompiler
 */
class SSACompilerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::process
     * @covers ::implementSSA
     */
    public function testOperation()
    {
        $graph = new DirectedAdjacencyList();
        $a = new Variable();
        $func = new Function_([$a], new Type(0), $graph);
        $noOp = new NoOp();
        $jumpZ = new JumpZ($noOp, $a);
        $end = new End();
        $graph->addDirectedEdge($func, $jumpZ);
        $graph->addDirectedEdge($jumpZ, $r1 = new Return_($a));
        $graph->addDirectedEdge($r1, $end);
        $graph->addDirectedEdge($jumpZ, $noOp);
        $graph->addDirectedEdge($noOp, $r2 = new Return_($b = new Variable()));
        $graph->addDirectedEdge($r2, $end);
        $state = new GraphState($func);
        $compiler = new SSACompiler();
        $compiler->process($state);
        $this->assertSame($a, $r1->getValue());
        $this->assertSame($b, $r2->getValue());
    }

    /**
     * @covers ::implementSSA
     */
    public function testOperationRevisitedPhi()
    {
        $graph = $this->getMock(DirectedAdjacencyList::class);

        $a = new Variable();
        $b = new Variable();
        $vertex = new NoOp();

        $graph->expects($this->once())
            ->method('eachAdjacent')
            ->with($this->identicalTo($vertex))
            ->will($this->returnValue([]));

        $phiNodes = new \SplObjectStorage();
        $phiNodes[$vertex] = new Vertex\Phi(new Variable());
        $compiler = new SSACompiler();
        $compiler->implementSSA($a, $b, $vertex, $graph, $phiNodes, []);
        $this->assertContains($b, $phiNodes[$vertex]->getValues());
    }

    /**
     * @covers ::__construct
     * @covers ::process
     * @covers ::implementSSA
     */
    public function testOperationPass2()
    {
        $graph = new DirectedAdjacencyList();
        $a = new Variable();
        $func = new Function_([$a], new Type(0), $graph);
        $noOp = new NoOp();
        $jumpZ = new JumpZ($noOp, $a);
        $end = new End();
        $graph->addDirectedEdge($func, $jumpZ);
        $graph->addDirectedEdge($jumpZ, $r1 = new Return_($a));
        $graph->addDirectedEdge($r1, $end);
        $graph->addDirectedEdge($jumpZ, $noOp);
        $graph->addDirectedEdge($noOp, $binary = new BinaryOp(BinaryOp::PLUS, $a, new Constant(2), $a));
        $graph->addDirectedEdge($binary, $r2 = new Return_($a));
        $graph->addDirectedEdge($r2, $end);
        $state = new GraphState($func);
        $compiler = new SSACompiler();

        $compiler->process($state);

        $this->assertSame($a, $r1->getValue());
        $this->assertSame($a, $binary->getA());
        $b = $binary->getResult();
        $this->assertSame($b, $r2->getValue());
    }

    /**
     * @covers ::process
     * @covers ::implementSSA
     */
    public function testOperationLoopWithPhi()
    {
        $graph = new DirectedAdjacencyList();
        $a = new Variable();
        $func = new Function_([$a], new Type(0), $graph);
        $start = new NoOp();
        $noOp = new NoOp();
        $jumpz = new JumpZ($noOp, $a);

        $graph->addDirectedEdge($func, $start);
        $graph->addDirectedEdge($start, $jumpz);
        $graph->addDirectedEdge($jumpz, $r = new Return_($a));
        $graph->addDirectedEdge($r, new End());
        $graph->addDirectedEdge($jumpz, $binary = new BinaryOp(BinaryOp::PLUS, $a, new Constant(2), $a));
        $graph->addDirectedEdge($binary, $j = new Jump());
        $graph->addDirectedEdge($j, $start);

        $state = new GraphState($func);
        $compiler = new SSACompiler();

        $compiler->process($state);

        $this->assertSame([$a], $func->getArguments());
        $i = 0;
        foreach ($graph->eachAdjacent($start) as $v) {
            $this->assertEquals(0, $i++, 'More then one adjacent node');
            $this->assertInstanceOf(Phi::class, $v);
            $this->assertContains($a, $v->getValues());
            $this->assertSame($r->getValue(), $v->getResult());
        }
    }

    /**
     * @covers ::findAssignmentsByVar
     */
    public function testFindAssignmentsByVar()
    {
        $assign1 = $this->getMock(Assign::class, [], [], '', false);
        $assign1->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue(new Variable()));
        $assign2 = $this->getMock(Assign::class, [], [], '', false);
        $assign2->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($t = new Variable()));
        $func1 = $this->getMock(Function_::class, [], [], '', false);
        $func1->expects($this->once())
            ->method('hasArgument')
            ->with($this->identicalTo($t))
            ->will($this->returnValue(false));
        $func2 = $this->getMock(Function_::class, [], [], '', false);
        $func2->expects($this->once())
            ->method('hasArgument')
            ->with($this->identicalTo($t))
            ->will($this->returnValue(true));
        $compiler = new SSACompiler();
        $this->assertSame([$assign2, $func2], $compiler->findAssignmentsByVar($t, [$assign1, $assign2, $func1, $func2]));
    }

    /**
     * @covers ::findFrontier
     */
    public function testFindFrontier()
    {
        $dominator = $this->getMock(Dominator::class, [], [], '', false);
        $nodes = [
            $this->getMock(Vertex::class),
            $this->getMock(Vertex::class),
        ];
        $dominator->expects($this->exactly(2))
            ->method('getFrontier')
            ->withConsecutive(
                [$this->identicalTo($nodes[0])],
                [$this->identicalTo($nodes[1])]
            )
            ->will($this->onConsecutiveCalls(
                [$a = $this->getMock(Vertex::class)],
                [$b = $this->getMock(Vertex::class)]
            ));
        $compiler = new SSACompiler();
        $this->assertSame([$a, $b], $compiler->findFrontier($nodes, $dominator));
    }

    /**
     * @covers ::findPhiNodes
     */
    public function testFindPhiNodesEmptyList()
    {
        $graph = new DirectedAdjacencyList();
        $dominator = $this->getMock(Dominator::class, [], [], '', false);
        $dominator->expects($this->never())
            ->method('getFrontier');
        $compiler = new SSACompiler();
        $this->assertEquals(0, count($compiler->findPhiNodes(new Variable(), [], $dominator, $graph)));
    }

    /**
     * @covers ::findPhiNodes
     */
    public function testFindPhiNodes()
    {
        $graph = new DirectedAdjacencyList();
        $dominator = $this->getMock(Dominator::class, [], [], '', false);
        $v1 = new Variable();
        $assignments = [
            $a1 = $this->getMock(Assign::class, [], [], '', false),
            $a2 = $this->getMock(Assign::class, [], [], '', false),
        ];
        $p1 = $this->getMock(Assign::class, [], [], '', false);
        $p2 = $this->getMock(NoOp::class);
        $p2->expects($this->once())
            ->method('getVariables')
            ->with()
            ->will($this->returnValue([$v1]));

        $dominator->expects($this->exactly(3))
            ->method('getFrontier')
            ->withConsecutive(
                [$this->identicalTo($a1)],
                [$this->identicalTo($a2)],
                [$this->identicalTo($p2)]
            )
            ->will($this->onConsecutiveCalls(
                [$p2],
                [],
                []
            ));
        $compiler = new SSACompiler();

        $this->assertSame([$p2], iterator_to_array($compiler->findPhiNodes($v1, $assignments, $dominator, $graph)));
    }

    /**
     * @covers ::findPhiNodes
     * @expectedException RuntimeException
     */
    public function testFindPhiNodesFailure()
    {
        $graph = new DirectedAdjacencyList();
        $dominator = $this->getMock(Dominator::class, [], [], '', false);
        $assignments = [
            $a1 = $this->getMock(Assign::class, [], [], '', false),
            $a2 = $this->getMock(Assign::class, [], [], '', false),
        ];
        $p1 = $this->getMock(Assign::class, [], [], '', false);
        $p2 = $this->getMock(NoOp::class);
        $dominator->expects($this->exactly(3))
            ->method('getFrontier')
            ->withConsecutive(
                [$this->identicalTo($a1)],
                [$this->identicalTo($a2)],
                [$this->identicalTo($p1)]
            )
            ->will($this->onConsecutiveCalls(
                [$p1],
                [],
                []
            ));
        $compiler = new SSACompiler();

        $this->assertSame([$p2], iterator_to_array($compiler->findPhiNodes(new Variable(), $assignments, $dominator, $graph)));
    }

}
