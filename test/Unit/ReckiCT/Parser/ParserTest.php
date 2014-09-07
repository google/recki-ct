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
 */

namespace ReckiCT\Parser;

use PHPUnit_Framework_TestCase as TestCase;

use Gliph\Graph\DirectedAdjacencyList;

use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Return_ as AstReturn;
use PhpParser\Node\Stmt\Function_ as AstFunction;
use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Type;
use ReckiCT\Graph\Vertex\End as JitEnd;
use ReckiCT\Graph\Vertex\NoOp as JitNoOp;
use ReckiCT\Graph\Vertex\Return_ as JitReturn;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;
use ReckiCT\Graph\Constant as JitConstant;
use ReckiCT\Graph\Variable as JitVariable;

/**
 * @coversDefaultClass \ReckiCT\Parser\Parser
 */
class ParserTest extends TestCase
{
    /**
     * @covers ::parseFunction
     * @covers ::addEndNode
     */
    public function testParserNoStmts()
    {
        $parser = new Parser();
        $node = new AstFunction('Foo');
        $node->jitType = new Type(Type::TYPE_LONG);
        $func = $parser->parseFunction($node);

        $this->assertInstanceOf(JitFunction::class, $func);
        $this->assertSame($node->jitType, $func->getReturnType());
        foreach ($func->getGraph()->edges() as $edge) {
            $this->assertSame($func, $edge[0]);
            $this->assertInstanceOf(JitEnd::class, $edge[1]);
        }
    }

    /**
     * @covers ::parseFunction
     * @covers ::addEndNode
     */
    public function testParserNoStmtsWithArgs()
    {
        $parser = new Parser();
        $node = new AstFunction(
            'Foo',
            [
                'params' => [
                    $a = new Param('a'),
                    $b = new Param('b'),
                ]
            ]
        );
        $node->jitType = new Type(Type::TYPE_LONG);
        $func = $parser->parseFunction($node);

        $this->assertInstanceOf(JitFunction::class, $func);
        $this->assertSame($node->jitType, $func->getReturnType());

        $this->assertEquals(2, count($func->getArguments()));
    }

    /**
     * @covers ::parseStmtList
     * @covers ::parseNode
     * @covers ::addRule
     */
    public function testParserStmts()
    {
        $parser = new Parser();
        $a = new AstReturn(
            new LNumber(1)
        );
        $parser->addRule(new DummyRule(false));
        $parser->addRule(new DummyRule(true));
        $state = new DummyState();
        $parser->parseStmtList([$a], $state);

    }

    /**
     * @covers ::parseNode
     */
    public function testParseVariable()
    {
        $var = new AstVariable('a');
        $state = new DummyState();
        $state->scope['a'] = $ret = new JitVariable();

        $parser = new Parser();
        $this->assertSame($ret, $parser->parseNode($var, $state));
    }

    /**
     * @covers ::parseNode
     */
    public function testParseConstant()
    {
        $stmt = new LNumber(42);
        $parser = new Parser();
        $state = new DummyState();
        $constant = $parser->parseNode($stmt, $state);
        $this->assertInstanceOf(JitConstant::class, $constant);
        $this->assertEquals(42, $constant->getValue());
    }

    /**
     * @covers ::parseNode
     * @expectedException LogicException
     */
    public function testParserFailureUnknownNode()
    {
        $parser = new Parser();
        $a = new AstReturn(
            new LNumber(1)
        );
        $state = new DummyState();

        $parser->parseNode($a, $state);
    }

    /**
     * @covers ::addEndNode
     */
    public function testEndNodeIsAdded()
    {
        $state = new DummyState();
        $state->graph = new DirectedAdjacencyList();
        $a = new JitReturn(new JitVariable());
        $b = new JitReturn(new JitVariable());
        $c = new JitNoOp();
        $state->graph->ensureArc($c, $a);
        $state->graph->ensureArc($c, $b);
        $parser = new Parser();

        $parser->addEndNode($state);

        $this->assertAdjacent($state, $a, JitEnd::class, 1);
        $this->assertAdjacent($state, $b, JitEnd::class, 1);
        $this->assertAdjacent($state, $c, JitReturn::class, 2);
    }

    protected function assertAdjacent($state, $node, $class, $count)
    {
        $ctr = 0;
        foreach ($state->graph->successorsOf($node) as $next) {
            $ctr++;
            $this->assertInstanceOf($class, $next);
        }
        $this->assertEquals($count, $ctr, 'Correct number of adjacent nodes');
    }

}
