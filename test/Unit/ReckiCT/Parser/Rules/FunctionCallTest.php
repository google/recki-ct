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

use PhpParser\Node\Arg as AstArg;
use PhpParser\Node\Name as AstName;
use PhpParser\Node\Expr\FuncCall as AstFunctionCall;
use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Type;
use ReckiCT\Signature;
use ReckiCT\Graph\Vertex\FunctionCall as JitFunctionCall;
use ReckiCT\Graph\Variable as JitVariable;
use ReckiCT\Graph\Dumper;

/**
 * @coversDefaultClass \ReckiCT\Parser\Rules\FunctionCall
 */
class FunctionCallTest extends RuleBase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new FunctionCall();
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
    public function testFunctionCallNoe()
    {
        $node = new AstFunctionCall(
            new AstName('foo'),
            []
        );
        $this->assertTrue($this->parser->test($node));
    }

    /**
     * @covers ::parse
     */
    public function testParseFunctionCall()
    {
        $node = new AstFunctionCall(
            new AstName('foo'),
            [
                new AstArg($a = new AstVariable('a')),
                new AstArg($b = new AstVariable('b')),
            ]
        );

        $node->signature = new Signature(new Type(Type::TYPE_LONG), []);

        $this->state->parser->shouldReceive('parseNode')->andReturn($x = new JitVariable(), $y = new JitVariable());

        $var = $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(JitVariable::class, $var);
        $this->assertInstanceOf(JitFunctionCall::class, $this->state->last);
        $this->assertSame([$x, $y], $this->state->last->getArguments());
        $this->assertSame($node->signature, $this->state->last->getSignature());

        $graph = ['NoOp FunctionCall'];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

    /**
     * @covers ::parse
     * @expectedException LogicException
     */
    public function testParseVariableFunctionCallShouldFail()
    {
        $node = new AstFunctionCall(
            new AstVariable('foo'),
            []
        );
        $this->parser->parse($node, $this->state);
    }
}
