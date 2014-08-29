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
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Vertex\Phi as JitPhi;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\OptimizerRule\Phi
 */
class PhiTest extends TestCase
{
    /**
     * @covers ::process
     */
    public function testNoChange()
    {
        $rule = new Phi();
        $this->assertFalse($rule->process($this->getMock(Vertex::class), $this->getMock(DirectedAdjacencyList::class)));
    }

    /**
     * @covers ::process
     */
    public function testChange()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $vertex->addValue($a = new Variable(new Type(Type::TYPE_LONG)));
        $this->assertTrue($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));

        $this->assertEquals(Type::TYPE_LONG, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testChangeLongAndNumeric()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $vertex->addValue(new Variable(new Type(Type::TYPE_LONG)));
        $vertex->addValue(new Variable(new Type(Type::TYPE_NUMERIC)));

        $this->assertTrue($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
        $this->assertEquals(Type::TYPE_LONG, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testChangeDoubleAndNumeric()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $vertex->addValue(new Variable(new Type(Type::TYPE_DOUBLE)));
        $vertex->addValue(new Variable(new Type(Type::TYPE_NUMERIC)));

        $this->assertTrue($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
        $this->assertEquals(Type::TYPE_DOUBLE, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testChangeBooleanAndNumeric()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $vertex->addValue(new Variable(new Type(Type::TYPE_BOOLEAN)));
        $vertex->addValue(new Variable(new Type(Type::TYPE_NUMERIC)));

        $this->assertTrue($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
        $this->assertEquals(Type::TYPE_BOOLEAN, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testChangeWithSkipped()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $vertex->addValue(new Variable(new Type(Type::TYPE_LONG)));
        $vertex->addValue(new Variable(new Type(Type::TYPE_UNKNOWN)));

        $this->assertFalse($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));

    }

    /**
     * @covers ::process
     */
    public function testChangeSkipped()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $vertex->addValue(new Variable(new Type(Type::TYPE_LONG)));
        $vertex->addValue(new Variable(new Type(Type::TYPE_DOUBLE)));
        $this->assertFalse($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
    }

    /**
     * @covers ::process
     */
    public function testChangeNoValues()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable());
        $this->assertFalse($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
    }

    /**
     * @covers ::process
     */
    public function testWithoutChange()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable(new Type(Type::TYPE_DOUBLE)));
        $this->assertFalse($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));

        $this->assertEquals(Type::TYPE_DOUBLE, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testRemoveCountedPhi()
    {
        $rule = new Phi();
        $vertex = new JitPhi($r = new Variable(new Type(Type::TYPE_DOUBLE)));
        $vertex->addValue($v = new Variable(new Type(Type::TYPE_DOUBLE)));

        $v2 = $this->getMock(Vertex\Assign::class, [], [], '', false);
        $v2->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($r));
        $v2->expects($this->once())
            ->method('setResult')
            ->with($this->identicalTo($v));

        $graph = $this->getMock(DirectedAdjacencyList::class);
        $graph->expects($this->exactly(1))
            ->method('eachVertex')
            ->will($this->returnValue(call_user_func(function () use ($vertex, $v2) {
                yield $vertex => array();
                yield $v2 => array();
            })));
        $transposed = $this->getMock(DirectedAdjacencyList::class);
        $transposed->expects($this->once())
            ->method('eachAdjacent')
            ->with($this->identicalTo($vertex))
            ->will($this->returnValue([]));
        $graph->expects($this->once())
            ->method('transpose')
            ->will($this->returnValue($transposed));
        $this->assertTrue($rule->process($vertex, $graph));
    }
}
