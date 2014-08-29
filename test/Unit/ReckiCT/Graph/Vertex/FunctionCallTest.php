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

use ReckiCT\Signature;
use ReckiCT\Type;

/**
 * @coversDefaultClass \ReckiCT\Graph\Vertex\FunctionCall
 */
class FunctionCallTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new FunctionCall('foo', [new Variable(), new Variable()], new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertEquals('unknown_3 = foo(unknown_1, unknown_2)', (string) $a);
    }

    /**
     * @covers ::getFunctionName
     * @covers ::__construct
     * @covers ::getResult
     */
    public function testBasicFunctionality()
    {
        $vertex = new FunctionCall('foo', [], $result = new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertEquals('foo', $vertex->getFunctionName());
        $this->assertSame($result, $vertex->getResult());
    }

    /**
     * @covers ::isSelfRecursive
     */
    public function testIsSelfRecursiveNot()
    {
        $vertex = new FunctionCall('foo', [], new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []), false);
        $this->assertFalse($vertex->isSelfRecursive());
    }

    /**
     * @covers ::isSelfRecursive
     */
    public function testIsSelfRecursiveIs()
    {
        $vertex = new FunctionCall('foo', [], new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []), true);
        $this->assertTrue($vertex->isSelfRecursive());
    }

    /**
     * @covers ::isIdempotent
     */
    public function testIsIdempotent()
    {
        $vertex = new FunctionCall('foo', [], new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertFalse($vertex->isIdempotent());
    }

    /**
     * @covers ::getArguments
     */
    public function testGetArguments()
    {
        $vertex = new FunctionCall('foo', $a = [new Variable()], new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertSame($a, $vertex->getArguments());
    }

    /**
     * @covers ::hasArgument
     */
    public function testHasArgument()
    {
        $b = new Variable();
        $vertex = new FunctionCall('foo', [$a = new Variable()], new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertTrue($vertex->hasArgument($a));
        $this->assertFalse($vertex->hasArgument($b));
    }

    /**
     * @covers ::getSignature
     */
    public function testGetSignature()
    {
        $b = new Variable();
        $vertex = new FunctionCall('foo', [$a = new Variable()], new Variable(), $sig = new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertSame($sig, $vertex->getSignature());
    }

    /**
     * @covers ::getVariables
     */
    public function testGetVariables()
    {
        $vertex = new FunctionCall('foo', [$a = new Variable()], $b = new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $this->assertSame([$a, $b], $vertex->getVariables());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testGetVariablesArg()
    {
        $vertex = new FunctionCall('foo', [$a = new Variable()], $b = new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $vertex->replaceVariable($a, $new = new Variable());
        $this->assertSame([$new, $b], $vertex->getVariables());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testReplaceVariablesResultDoestAffect()
    {
        $vertex = new FunctionCall('foo', [$a = new Variable()], $b = new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $vertex->replaceVariable($b, $new = new Variable());
        $this->assertSame([$a, $b], $vertex->getVariables());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testReplaceVariableNeither()
    {
        $vertex = new FunctionCall('foo', [$a = new Variable()], $b = new Variable(), new Signature(new Type(Type::TYPE_UNKNOWN), []));
        $vertex->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$a, $b], $vertex->getVariables());
    }

}
