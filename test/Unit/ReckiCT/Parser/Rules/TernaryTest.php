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

use PhpParser\Node\Expr\Ternary as AstTernary;
use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Graph\Variable as JitVariable;
use ReckiCT\Graph\Dumper;

/**
 * @coversDefaultClass \ReckiCT\Parser\Rules\Ternary
 */
class TernaryTest extends RuleBase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new Ternary();
        parent::setUp();
    }

    /**
     * @covers ::test
     */
    public function testOtherNode()
    {
        $node = new AstVariable('a');
        $this->assertFalse($this->parser->test($node));
    }

    /**
     * @covers ::test
     */
    public function testTernaryNode()
    {
        $node = new AstTernary(
            new AstVariable('a'),
            new AstVariable('b'),
            new AstVariable('c')
        );
        $this->assertTrue($this->parser->test($node));
    }

    /**
     * @covers ::parse
     */
    public function testParseTernaryWithIf()
    {
        $node = new AstTernary(
            new AstVariable('a'),
            new AstVariable('b'),
            new AstVariable('c')
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn(new JitVariable(), new JitVariable(), new JitVariable());

        $var = $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(JitVariable::class, $var);

        $graph = [
            'NoOp JumpZ',
            'JumpZ NoOp',
            'JumpZ Assign',
            'NoOp Assign',
            'Assign Jump',
            'Jump NoOp',
            'Assign NoOp',

        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

    /**
     * @covers ::parse
     */
    public function testParseTernaryWithoutIf()
    {
        $node = new AstTernary(
            new AstVariable('a'),
            null,
            new AstVariable('c')
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn(new JitVariable(), new JitVariable());

        $var = $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(JitVariable::class, $var);

        $graph = [
            'NoOp JumpZ',
            'JumpZ NoOp',
            'JumpZ Assign',
            'NoOp Assign',
            'Assign Jump',
            'Jump NoOp',
            'Assign NoOp',

        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

}
