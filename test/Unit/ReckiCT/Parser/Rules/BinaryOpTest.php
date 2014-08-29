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

use ReckiCT\Graph\Variable as JitVariable;
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;

use ReckiCT\Graph\Dumper;

use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use PhpParser\Node\Expr\BinaryOp\ShiftLeft;
use PhpParser\Node\Expr\BinaryOp\ShiftRight;

/**
 * @coversDefaultClass \ReckiCT\Parser\Rules\BinaryOp
 */
class BinaryOpTest extends RuleBase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new BinaryOp();
        parent::setUp();
    }

    public static function provideValidOps()
    {
        $a = new AstVariable('a');
        $b = new AstVariable('b');

        return [
            [new Plus($a, $b)],
            [new Minus($a, $b)],
            [new Mul($a, $b)],
            [new Div($a, $b)],
            [new Mod($a, $b)],
            [new Equal($a, $b)],
            [new NotEqual($a, $b)],
            [new Identical($a, $b)],
            [new NotIdentical($a, $b)],
            [new Greater($a, $b)],
            [new GreaterOrEqual($a, $b)],
            [new Smaller($a, $b)],
            [new SmallerOrEqual($a, $b)],
            [new BitwiseAnd($a, $b)],
            [new BitwiseOr($a, $b)],
            [new BitwiseXor($a, $b)],
            [new ShiftRight($a, $b)],
            [new ShiftLeft($a, $b)],
        ];
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
     * @dataProvider provideValidOps
     */
    public function testValidOp($node)
    {
        $this->assertTrue($this->parser->test($node));
    }

    /**
     * @covers ::parse
     */
    public function testParse()
    {
        $node = new Plus(
            $a = new AstVariable('a'),
            $b = new AstVariable('b')
        );

        $this->state->parser->shouldReceive('parseNode')->andReturn(new JitVariable(), new JitVariable());

        $var = $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(JitVariable::class, $var);
        $this->assertInstanceOf(JitBinaryOp::class, $this->state->last);

        $graph = ["NoOp BinaryOp"];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

}
