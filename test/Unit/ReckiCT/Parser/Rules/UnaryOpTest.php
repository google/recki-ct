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

use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Graph\Constant as JitConstant;
use ReckiCT\Graph\Variable as JitVariable;

use PhpParser\Node\Expr\UnaryMinus as AstUnaryMinus;
use PhpParser\Node\Expr\BooleanNot as AstBooleanNot;
use PhpParser\Node\Expr\BitwiseNot as AstBitwiseNot;
use ReckiCT\Graph\Vertex\BitwiseNot as JitBitwiseNot;

use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;
use ReckiCT\Graph\Dumper;

/**
 * @coversDefaultClass \ReckiCT\Parser\Rules\UnaryOp
 */
class UnaryOpTest extends RuleBase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new UnaryOp();
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
    public function testBitwiseNot()
    {
        $node = new AstBitwiseNot(
            $var = new AstVariable('a')
        );

        $this->assertTrue($this->parser->test($node));
    }

    /**
     * @covers ::test
     */
    public function testUnaryMinus()
    {
        $node = new AstUnaryMinus(
            $var = new AstVariable('a')
        );

        $this->assertTrue($this->parser->test($node));
    }

    /**
     * @covers ::test
     */
    public function testBooleanNot()
    {
        $node = new AstBooleanNot(
            $var = new AstVariable('a')
        );

        $this->assertTrue($this->parser->test($node));
    }

    /**
     * @covers ::parse
     */
    public function testParse()
    {
        $node = new AstBitwiseNot(
            $expr = new AstVariable('a')
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn($b = new JitVariable());

        $var = $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(JitVariable::class, $var);
        $this->assertInstanceOf(JitBitwiseNot::class, $this->state->last);
        $this->assertSame($b, $this->state->last->getValue());

        $graph = [
            'NoOp BitwiseNot',
        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

    /**
     * @covers ::parse
     */
    public function testParseUnaryMinus()
    {
        $node = new AstUnaryMinus(
            $expr = new AstVariable('a')
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn($b = new JitVariable());

        $var = $this->parser->parse($node, $this->state);

        $this->assertInstanceOf(JitVariable::class, $var);
        $this->assertInstanceOf(JitBinaryOp::class, $this->state->last);
        $this->assertSame($b, $this->state->last->getB());
        $this->assertInstanceOf(JitConstant::class, $this->state->last->getA());

        $graph = [
            'NoOp BinaryOp',
        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

}
