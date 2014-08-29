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
 * @subpackage Vertex
 */

namespace ReckiCT\Graph\Vertex;

use PHPUnit_Framework_TestCase as TestCase;

use ReckiCT\Graph\Variable;

/**
 * @coversDefaultClass \ReckiCT\Graph\Vertex\BinaryOp
 */
class BinaryOpTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new BinaryOp(BinaryOp::PLUS, new Variable(), new Variable(), new Variable());
        $this->assertEquals('unknown_3 = unknown_1 + unknown_2', (string) $a);
    }

    /**
     * @covers ::__construct
     * @covers ::getVariables
     * @covers ::getResult
     * @covers ::getKind
     * @covers ::getA
     * @covers ::getB
     */
    public function testBasicConstruct()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, $a = new Variable(), $b = new Variable(), $result = new Variable());
        $this->assertSame([$a, $b, $result], $vertex->getVariables());
        $this->assertSame($a, $vertex->getA());
        $this->assertSame($b, $vertex->getB());
        $this->assertSame($result, $vertex->getResult());
        $this->assertEquals(BinaryOp::PLUS, $vertex->getKind());
    }

    /**
     * @covers ::isIdempotent
     */
    public function testIsIdempotent()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, new Variable(), new Variable(), new Variable());
        $this->assertTrue($vertex->isIdempotent());
    }

    /**
     * @covers ::setResult
     */
    public function testSetResult()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, $a = new Variable(), $b = new Variable(), new Variable());
        $vertex->setResult($new = new Variable());
        $this->assertSame([$a, $b, $new], $vertex->getVariables());
        $this->assertSame($new, $vertex->getResult());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariableA()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, $a = new Variable(), $b = new Variable(), $result = new Variable());
        $vertex->replaceVariable($a, $new = new Variable());
        $this->assertSame([$new, $b, $result], $vertex->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariableB()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, $a = new Variable(), $b = new Variable(), $result = new Variable());
        $vertex->replaceVariable($b, $new = new Variable());
        $this->assertSame([$a, $new, $result], $vertex->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariableResultNotAffected()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, $a = new Variable(), $b = new Variable(), $result = new Variable());
        $vertex->replaceVariable($result, $new = new Variable());
        $this->assertSame([$a, $b, $result], $vertex->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariableNeither()
    {
        $vertex = new BinaryOp(BinaryOp::PLUS, $a = new Variable(), $b = new Variable(), $result = new Variable());
        $vertex->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$a, $b, $result], $vertex->getVariables());
    }

}
