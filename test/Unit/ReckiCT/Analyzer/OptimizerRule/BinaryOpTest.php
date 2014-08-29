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
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\OptimizerRule\BinaryOp
 */
class BinaryOpTest extends TestCase
{
    public static function provideTestValid()
    {
        return [
            [JitBinaryOp::PLUS, Type::TYPE_LONG, Type::TYPE_LONG, Type::TYPE_NUMERIC],
            [JitBinaryOp::PLUS, Type::TYPE_DOUBLE, Type::TYPE_LONG, Type::TYPE_DOUBLE],
            [JitBinaryOp::PLUS, Type::TYPE_LONG, Type::TYPE_DOUBLE, Type::TYPE_DOUBLE],
            [JitBinaryOp::PLUS, Type::TYPE_NUMERIC, Type::TYPE_NUMERIC, Type::TYPE_NUMERIC],
            [JitBinaryOp::PLUS, Type::TYPE_NUMERIC, Type::TYPE_LONG, Type::TYPE_NUMERIC],
            [JitBinaryOp::PLUS, Type::TYPE_NUMERIC, Type::TYPE_DOUBLE, Type::TYPE_DOUBLE],
            [JitBinaryOp::MINUS, Type::TYPE_LONG, Type::TYPE_LONG, Type::TYPE_NUMERIC],
            [JitBinaryOp::MUL, Type::TYPE_LONG, Type::TYPE_LONG, Type::TYPE_NUMERIC],
            [JitBinaryOp::MUL, Type::TYPE_UNKNOWN, Type::TYPE_DOUBLE, Type::TYPE_DOUBLE],
            [JitBinaryOp::MUL, Type::TYPE_UNKNOWN, Type::TYPE_UNKNOWN, Type::TYPE_NUMERIC],

            [JitBinaryOp::DIV, Type::TYPE_LONG, Type::TYPE_LONG, Type::TYPE_DOUBLE],
            [JitBinaryOp::DIV, Type::TYPE_NUMERIC, Type::TYPE_LONG, Type::TYPE_DOUBLE],

            [JitBinaryOp::EQUAL, Type::TYPE_NUMERIC, Type::TYPE_LONG, Type::TYPE_BOOLEAN],

            [JitBinaryOp::BITWISE_AND, Type::TYPE_NUMERIC, Type::TYPE_LONG, Type::TYPE_LONG],
            [JitBinaryOp::BITWISE_AND, Type::TYPE_STRING, Type::TYPE_STRING, Type::TYPE_STRING],
        ];
    }

    /**
     * @covers ::process
     */
    public function testNoChange()
    {
        $rule = new BinaryOp();
        $this->assertFalse($rule->process($this->getMock(Vertex::class), $this->getMock(DirectedAdjacencyList::class)));
    }

    /**
     * @covers ::process
     * @dataProvider provideTestValid
     */
    public function testValidOp($kind, $aType, $bType, $rType)
    {
        $rule = new BinaryOp();
        $vertex = new JitBinaryOp(
            $kind,
            $a = new Variable(new Type($aType)),
            $b = new Variable(new Type($bType)),
            $r = new Variable()
        );
        $this->assertTrue($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));

        $this->assertEquals($rType, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testInValidOp()
    {
        $rule = new BinaryOp();
        $vertex = new JitBinaryOp(
            JitBinaryOp::PLUS,
            $a = new Variable(),
            $b = new Variable(),
            $r = new Variable()
        );
        $this->assertTrue($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
        $this->assertEquals(Type::TYPE_NUMERIC, $r->getType()->getType());
    }

    /**
     * @covers ::process
     */
    public function testInValidKind()
    {
        $rule = new BinaryOp();
        $vertex = new JitBinaryOp(
            -1,
            $a = new Variable(),
            $b = new Variable(),
            $r = new Variable()
        );
        $this->assertFalse($rule->process($vertex, $this->getMock(DirectedAdjacencyList::class)));
        $this->assertEquals(Type::TYPE_UNKNOWN, $r->getType()->getType());
    }

}
