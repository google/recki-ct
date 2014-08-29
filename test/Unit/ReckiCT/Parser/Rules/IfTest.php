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
 * @package Parser
 * @subpackage Rules
 */

namespace ReckiCT\Parser\Rules;

require_once __DIR__ . '/RuleBase.php';

use PhpParser\Node\Stmt\If_ as AstIf;
use PhpParser\Node\Stmt\Else_ as AstElse;
use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Graph\Vertex\JumpZ;
use ReckiCT\Graph\Vertex\Jump;
use ReckiCT\Graph\Vertex\NoOp;
use ReckiCT\Graph\Variable as JitVariable;

use ReckiCT\Graph\Dumper;

/**
 * @coversDefaultClass \ReckiCT\Parser\Rules\If_
 */
class IfTest extends RuleBase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new If_();
        parent::setUp();
    }

    /**
     * @covers ::test
     */
    public function testTestPass()
    {
        $this->assertTrue($this->parser->test(new AstIf(new AstVariable('a'))));
    }

    /**
     * @covers ::test
     */
    public function testTestFail()
    {
        $this->assertFalse($this->parser->test(new AstVariable('test')));
    }

    /**
     * @covers ::parse
     */
    public function testParseIf()
    {
        $node = new AstIf(
            $a = new AstVariable('a'),
            [
                'stmts' => [
                    $b = new AstVariable('b')
                ]
            ]
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn($jitA = new JitVariable());

        $this->state->parser->shouldReceive('parseStmtList');

        $this->parser->parse($node, $this->state);

        $graph = [
            'NoOp JumpZ', // Start to the JumpZ
            // body goes here
            'JumpZ Jump', // Jump to the end
            'JumpZ NoOp', // JumpZ to end
            'Jump NoOp', // Jump to end
        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

    /**
     * @covers ::parse
     */
    public function testParseIfWithElse()
    {
        $node = new AstIf(
            $a = new AstVariable('a'),
            [
                'stmts' => [
                    $b = new AstVariable('b'),
                ],
                'else' => new AstElse([
                    $c = new AstVariable('c'),
                ])
            ]
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn($jitA = new JitVariable());

        $this->state->parser->shouldReceive('parseStmtList');

        $this->parser->parse($node, $this->state);

        $this->assertInstanceOf(NoOp::class, $this->state->last);

        $graph = [
            'NoOp JumpZ',
            'JumpZ NoOp',
            'JumpZ Jump',
            'NoOp Jump',
            'Jump NoOp',
            'Jump NoOp',
        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));

    }

}
