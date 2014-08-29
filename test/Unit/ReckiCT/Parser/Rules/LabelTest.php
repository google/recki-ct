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

use PhpParser\Node\Stmt\Label as AstLabel;
use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Graph\Vertex\Jump;
use ReckiCT\Graph\Vertex\NoOp;
use ReckiCT\Graph\Dumper;

/**
 * @coversDefaultClass \ReckiCT\Parser\Rules\Label
 */
class LabelTest extends RuleBase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new Label();
        parent::setUp();
    }

    /**
     * @covers ::test
     */
    public function testTestPass()
    {
        $this->assertTrue($this->parser->test(new AstLabel('test')));
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
    public function testParse()
    {
        $node = new AstLabel('foo');

        $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(NoOp::class, $this->state->last);

        $graph = [
            'NoOp NoOp',
        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

    /**
     * @covers ::parse
     * @expectedException RuntimeException
     */
    public function testParseFailureWithLabelCollision()
    {
        $node = new AstLabel('foo');

        $this->state->labels['foo'] = new NoOp();

        $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(NoOp::class, $this->state->last);
    }

    /**
     * @covers ::parse
     */
    public function testParseWithGotoQueue()
    {
        $node = new AstLabel('foo');

        $this->state->gotolist['foo'] = [$j = new Jump()];

        $this->parser->parse($node, $this->state);
        $this->assertInstanceOf(NoOp::class, $this->state->last);

        $graph = [
            'NoOp NoOp',
            'Jump NoOp',
        ];
        $this->assertEquals($graph, Dumper::dump($this->state->graph));
    }

}
