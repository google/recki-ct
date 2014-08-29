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
 * @subpackage AstProcessor
 */

namespace ReckiCT\Analyzer\AstProcessor;

use PHPUnit_Framework_TestCase as TestCase;
use PhpParser\NodeTraverser;

use ReckiCT\Signature;
use ReckiCT\Type;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Param;
use PhpParser\Node\Name;

/**
 * @coversDefaultClass ReckiCT\Analyzer\AstProcessor\SignatureResolver
 */
class SignatureResolverTest extends TestCase
{
    protected $traverser;
    protected $mockResolver;

    /**
     * @covers ::__construct
     */
    protected function setUp()
    {
        $this->mockResolver = $this->getMock('ReckiCT\Analyzer\SignatureResolver');
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new SignatureResolver($this->mockResolver));
    }

    /**
     * @covers ::__construct
     * @covers ::enterNode
     */
    public function testFunction()
    {
        $from = new Function_(
            'foo',
            [
                'params' => [
                    new Param('a')
                ]
            ]
        );
        $to = new Function_(
            'foo',
            [
                'params' => [
                    $a = new Param('a')
                ]
            ]
        );
        $this->mockResolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($from))
            ->willReturn(new Signature(
                new Type(Type::TYPE_VOID),
                [
                    new Type(Type::TYPE_LONG),
                ]
            ));
        $to->jitType = new Type(Type::TYPE_VOID);
        $a->jitType = new Type(Type::TYPE_LONG);
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::__construct
     * @covers ::enterNode
     */
    public function testFunctionCall()
    {
        $from = new FuncCall(
            new Name('foo'),
            [
                'params' => [
                    new Param('a')
                ]
            ]
        );
        $to = new FuncCall(
            new Name('foo'),
            [
                'params' => [
                    $a = new Param('a')
                ]
            ]
        );
        $this->mockResolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo('foo'))
            ->willReturn($sig = new Signature(
                new Type(Type::TYPE_VOID),
                [
                    new Type(Type::TYPE_LONG),
                ]
            ));
        $to->signature = $sig;

        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::__construct
     * @covers ::enterNode
     */
    public function testFunctionUnknown()
    {
        $from = new Function_(
            'foo'
        );
        $to = new Function_(
            'foo'
        );
        $this->mockResolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($from))
            ->willReturn(new Signature(
                new Type(Type::TYPE_UNKNOWN),
                []
            ));
        $to->jitType = new Type(Type::TYPE_UNKNOWN);
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::__construct
     * @expectedException LogicException
     * @covers ::enterNode
     */
    public function testParamTypeMismatch()
    {
        $from = new Function_(
            'foo',
            [
                'params' => [
                    new Param('a', null, 'StdClass')
                ]
            ]
        );
        $this->mockResolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($from))
            ->willReturn(new Signature(
                new Type(Type::TYPE_VOID),
                [
                    new Type(Type::TYPE_LONG),
                ]
            ));
        $this->traverser->traverse([$from]);
    }

    /**
     * @covers ::__construct
     * @covers ::enterNode
     */
    public function testUnknownParamType()
    {
        $from = new Function_(
            'foo',
            [
                'params' => [
                    new Param('a')
                ]
            ]
        );
        $to = new Function_(
            'foo',
            [
                'params' => [
                    $a = new Param('a')
                ]
            ]
        );
        $this->mockResolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($from))
            ->willReturn(new Signature(
                new Type(Type::TYPE_UNKNOWN),
                [
                    new Type(Type::TYPE_UNKNOWN),
                ]
            ));
        $to->jitType = new Type(Type::TYPE_UNKNOWN);
        $a->jitType = new Type(Type::TYPE_UNKNOWN);
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

}
