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
 * @subpackage OptimizerRule
 */

namespace ReckiCT\Analyzer\OptimizerRule;

use Gliph\Graph\DirectedAdjacencyList;
use PHPUnit_Framework_TestCase as TestCase;

use ReckiCT\Type;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Constant;
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Vertex\End;
use ReckiCT\Graph\Vertex\Assign as JitAssign;
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\OptimizerRule\ConstBinaryOp
 */
class ConstBinaryOpTest extends TestCase
{
    public static function provideTestExpansion()
    {
        return [
            [JitBinaryOp::PLUS, new Constant(1), new Constant(2), new Variable(new Type(Type::TYPE_LONG)), new Constant(3)],
            [JitBinaryOp::MINUS, new Constant(1.5), new Constant(0.5), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(1)],
            [JitBinaryOp::MUL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_LONG)), new Constant(100)],
            [JitBinaryOp::DIV, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(1)],
            [JitBinaryOp::MOD, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(0)],
            [JitBinaryOp::DIV, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(1)],
            [JitBinaryOp::EQUAL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(true)],
            [JitBinaryOp::NOT_EQUAL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(false)],
            [JitBinaryOp::IDENTICAL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(true)],
            [JitBinaryOp::NOT_IDENTICAL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(false)],
            [JitBinaryOp::GREATER, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(false)],
            [JitBinaryOp::GREATER_EQUAL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(true)],
            [JitBinaryOp::SMALLER, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(false)],
            [JitBinaryOp::SMALLER_EQUAL, new Constant(10), new Constant(10), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(true)],
            [JitBinaryOp::BITWISE_AND, new Constant(2), new Constant(4), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(0)],
            [JitBinaryOp::BITWISE_OR, new Constant(2), new Constant(4), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(6)],
            [JitBinaryOp::BITWISE_XOR, new Constant(6), new Constant(4), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(2)],
            [JitBinaryOp::SHIFT_LEFT, new Constant(2), new Constant(1), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(4)],
            [JitBinaryOp::SHIFT_RIGHT, new Constant(2), new Constant(1), new Variable(new Type(Type::TYPE_DOUBLE)), new Constant(1)],
        ];
    }

    /**
     * @covers ::process
     */
    public function testNoChange()
    {
        $rule = new ConstBinaryOp();
        $this->assertFalse($rule->process($this->getMock(Vertex::class), $this->getMock(DirectedAdjacencyList::class)));
    }

    /**
     * @covers ::process
     * @dataProvider provideTestExpansion
     */
    public function testExpansion($kind, $a, $b, $r, $const)
    {
        $graph = new DirectedAdjacencyList();
        $end = new End();
        $graph->addDirectedEdge($end, $b = new JitBinaryOp($kind, $a, $b, $r));
        $rule = new ConstBinaryOp();
        $this->assertTrue($rule->process($b, $graph));
        $i = 0;
        foreach ($graph->eachAdjacent($end) as $vtx) {
            $this->assertEquals(0, $i++, 'More than one adjacent vertex found!');
            $this->assertInstanceOf(JitAssign::class, $vtx);
            $this->assertSame($r, $vtx->getResult());
            $this->assertEquals($const->getValue(), $vtx->getValue()->getValue());
        }
        $this->assertEquals(1, $i, 'No adjacent vertex found!');
    }

    /**
     * @covers ::process
     */
    public function testExpansionFailure()
    {
        $graph = new DirectedAdjacencyList();
        $end = new End();
        $graph->addDirectedEdge($end, $b = new JitBinaryOp(JitBinaryOp::PLUS, new Constant(1), new Variable(), new Variable()));
        $rule = new ConstBinaryOp();
        $this->assertFalse($rule->process($b, $graph));
    }
}
