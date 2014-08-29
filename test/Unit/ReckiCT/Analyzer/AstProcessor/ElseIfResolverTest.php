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

use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Expr\Variable;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\AstProcessor\ElseIfResolver
 */
class ElseIfResolverTest extends TestCase
{
    protected $traverser;

    protected function setUp()
    {
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new ElseIfResolver());
    }

    /**
     * @covers ::leaveNode
     * @covers ::expandElseIfs
     */
    public function testRewrite()
    {
        $from = new If_(
            new Variable('a'),
            [
                'stmts' => [
                    new Variable('b'),
                ],
                'elseifs' => [
                    new ElseIf_(
                        new Variable('c'),
                        [new Variable('d')]
                    ),
                    new ElseIf_(
                        new Variable('e'),
                        [new Variable('f')]
                    )
                ],
                'else' => new Else_(
                    [new Variable('g')]
                )
            ]
        );
        $to = new If_(
            new Variable('a'),
            [
                'stmts' => [
                    new Variable('b'),
                ],
                'else' => new Else_([
                    new If_(new Variable('c'), [
                        'stmts' => [new Variable('d')],
                        'else' => new Else_([
                            new If_(new Variable('e'), [
                                'stmts' => [new Variable('f')],
                                'else' => new Else_([new Variable('g')])
                            ])
                        ])
                    ])
                ])

            ]
        );
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

}
