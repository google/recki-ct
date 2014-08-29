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

use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\Label;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\While_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\Continue_;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\BooleanNot;

use PhpParser\Node\Scalar\LNumber;

/**
 * @coversDefaultClass ReckiCT\Analyzer\AstProcessor\LoopResolver
 */
class LoopResolverTest extends TestCase
{
    protected $traverser;

    protected function setUp()
    {
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new LoopResolver());
    }

    protected function tearDown()
    {
        $r = new \ReflectionProperty('ReckiCT\Analyzer\AstProcessor\LoopResolver', 'labelCounter');
        $r->setAccessible(true);
        $r->setValue(0);
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     * @covers ::compileWhile
     * @covers ::makeLabel
     */
    public function testWhile()
    {
        $from = new While_(
            new Variable('a'),
            [new Variable('b')]
        );
        $to = [
            new Label('compiled_label_ReckiCT_0'),
            new If_(
                new BooleanNot(new Variable('a')),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_1')]]
            ),
            new Variable('b'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_1')
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::compileWhile
     */
    public function testWhileOptimization()
    {
        $from = new While_(
            new BooleanNot(new Variable('a')),
            [new Variable('b')]
        );
        $to = [
            new Label('compiled_label_ReckiCT_0'),
            new If_(
                new Variable('a'),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_1')]]
            ),
            new Variable('b'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_1')
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     * @covers ::compileDo
     * @covers ::makeLabel
     */
    public function testDoWhile()
    {
        $from = new Do_(
            new Variable('a'),
            [new Variable('b'), new Break_(), new Continue_()]
        );
        $to = [
            new Label('compiled_label_ReckiCT_2'), // Start label
            new Variable('b'),
            new Goto_('compiled_label_ReckiCT_1'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_0'), // Continue label
            new If_(
                new Variable('a'),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_2')]]
            ),
            new Label('compiled_label_ReckiCT_1') // break label
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     * @covers ::compileFor
     * @covers ::makeLabel
     */
    public function testFor()
    {
        $from = new For_([
            'init' => [new Variable('a')],
            'cond' => [new Variable('b'), new Variable('c')],
            'loop' => [new Variable('d')],
            'stmts' => [new Variable('e'), new Break_(), new Continue_()]
        ]);
        $to = [
            new Variable('a'),
            new Label('compiled_label_ReckiCT_2'), // Start label
            new Variable('b'),
            new If_(
                new BooleanNot(new Variable('c')),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_1')]]
            ),
            new Variable('e'),
            new Goto_('compiled_label_ReckiCT_1'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_0'),
            new Variable('d'),
            new Goto_('compiled_label_ReckiCT_2'),
            new Label('compiled_label_ReckiCT_1'),
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::compileFor
     */
    public function testForOptimization()
    {
        $from = new For_([
            'init' => [new Variable('a')],
            'cond' => [new Variable('b'), new BooleanNot(new Variable('c'))],
            'loop' => [new Variable('d')],
            'stmts' => [new Variable('e'), new Break_(), new Continue_()]
        ]);
        $to = [
            new Variable('a'),
            new Label('compiled_label_ReckiCT_2'), // Start label
            new Variable('b'),
            new If_(
                new Variable('c'),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_1')]]
            ),
            new Variable('e'),
            new Goto_('compiled_label_ReckiCT_1'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_0'),
            new Variable('d'),
            new Goto_('compiled_label_ReckiCT_2'),
            new Label('compiled_label_ReckiCT_1'),
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::compileFor
     */
    public function testForEmptyConditionOptimization()
    {
        $from = new For_([
            'init' => [new Variable('a')],
            'cond' => [],
            'loop' => [new Variable('d')],
            'stmts' => [new Variable('e'), new Break_(), new Continue_()]
        ]);
        $to = [
            new Variable('a'),
            new Label('compiled_label_ReckiCT_2'), // Start label
            new Variable('e'),
            new Goto_('compiled_label_ReckiCT_1'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_0'),
            new Variable('d'),
            new Goto_('compiled_label_ReckiCT_2'),
            new Label('compiled_label_ReckiCT_1'),
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     * @covers ::resolveStack
     * @covers ::makeLabel
     */
    public function testSwitch()
    {
        $from = new Switch_(
            new Variable('a'),
            [
                new Case_(
                    new Variable('b'),
                    [new Break_()]
                ),
                new Case_(
                    new Variable('c'),
                    [new Continue_()]
                ),
            ]
        );
        $to = [
            new Switch_(
                new Variable('a'),
                [
                    new Case_(
                        new Variable('b'),
                        [new Goto_('compiled_label_ReckiCT_0')]
                    ),
                    new Case_(
                        new Variable('c'),
                        [new Goto_('compiled_label_ReckiCT_0')]
                    ),
                ]
            ),
            new Label('compiled_label_ReckiCT_0'),
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     * @covers ::leaveNode
     * @covers ::compileWhile
     * @covers ::resolveStack
     * @covers ::makeLabel
     */
    public function testNestedLoop()
    {
        $from = new While_(
            new Variable('a'),
            [
                new While_(
                    new Variable('b'),
                    [
                        new Variable('c'),
                        new Break_(),
                        new Continue_(new LNumber(2))
                    ]
                )
            ]
        );
        $to = [
            new Label('compiled_label_ReckiCT_0'),
            new If_(
                new BooleanNot(new Variable('a')),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_1')]]
            ),
            new Label('compiled_label_ReckiCT_2'),
            new If_(
                new BooleanNot(new Variable('b')),
                ['stmts' => [new Goto_('compiled_label_ReckiCT_3')]]
            ),
            new Variable('c'),
            new Goto_('compiled_label_ReckiCT_3'),
            new Goto_('compiled_label_ReckiCT_2'),
            new Goto_('compiled_label_ReckiCT_2'),
            new Label('compiled_label_ReckiCT_3'),
            new Goto_('compiled_label_ReckiCT_0'),
            new Label('compiled_label_ReckiCT_1')
        ];
        $this->assertEquals($to, $this->traverser->traverse([$from]));
    }

    /**
     * @expectedException LogicException
     * @covers ::enterNode
     * @covers ::resolveStack
     */
    public function testInvalidBreak()
    {
        $from = new Break_(new Variable('a'));
        $this->traverser->traverse([$from]);
    }

    /**
     * @expectedException LogicException
     * @covers ::enterNode
     * @covers ::resolveStack
     */
    public function testInvalidBreakTooLarge()
    {
        $from = new While_(
            new Variable('a'),
            [
                new While_(
                    new Variable('b'),
                    [
                        new Variable('c'),
                        new Break_(),
                        new Continue_(new LNumber(3))
                    ]
                )
            ]
        );
        $this->traverser->traverse([$from]);
    }
}
