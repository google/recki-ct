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

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;

/**
 * @coversDefaultClass ReckiCT\Analyzer\AstProcessor\RecursionResolver
 */
class RecursionResolverTest extends TestCase
{
    protected $traverser;

    protected function setUp()
    {
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver());
        $this->traverser->addVisitor(new RecursionResolver());
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     */
    public function testSimpleRecursion()
    {
        $from = new Function_(
            'foo',
            [
                'stmts' => [
                    new FuncCall(new Name('foo'))
                ]
            ]
        );
        $to = new Function_(
            'foo',
            [
                'stmts' => [
                    $a = new FuncCall(new Name('foo'))
                ]
            ]
        );
        $to->namespacedName = new Name('foo');
        $a->isSelfRecursive = true;
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     */
    public function testComplexRecursion()
    {
        $from = new Function_(
            'foo',
            [
                'stmts' => [
                    new Function_(
                        'bar',
                        [
                            'stmts' => [
                                new FuncCall(new Name('foo')),
                            ]
                        ]
                    ),
                    new FuncCall(new Name('foo')),
                ]
            ]
        );
        $to = new Function_(
            'foo',
            [
                'stmts' => [
                    $a = new Function_(
                        'bar',
                        [
                            'stmts' => [
                                new FuncCall(new Name('foo')),
                            ]
                        ]
                    ),
                    $c = new FuncCall(new Name('foo')),
                ]
            ]
        );
        $to->namespacedName = new Name('foo');
        $a->namespacedName = new Name('bar');
        $c->isSelfRecursive = true;
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     */
    public function testVariableRecursion()
    {
        $from = new Function_(
            'foo',
            [
                'stmts' => [
                    new FuncCall(new Variable('a'))
                ]
            ]
        );
        $to = new Function_(
            'foo',
            [
                'stmts' => [
                    new FuncCall(new Variable('a'))
                ]
            ]
        );
        $to->namespacedName = new Name('foo');
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

}
