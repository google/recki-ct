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

use PhpParser\Node\Expr\Variable as AstVariable;

use ReckiCT\Graph\Variable as JitVariable;

use Phake as p;

/**
 * @coversDefaultClass \ReckiCT\Parser\State
 */
class StateTest extends TestCase
{
    protected $state;

    /**
     * @covers ::__construct
     */
    protected function setUp()
    {
        $this->state = new State(
            p::mock(Parser::class),
            new DirectedAdjacencyList()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::findVariable
     * @uses \ReckiCT\Graph\Variable
     * @uses \ReckiCT\Type
     */
    public function testFindVariableString()
    {
        $var = $this->state->findVariable('foo');
        $this->assertInstanceOf(JitVariable::class, $var);
        $this->assertSame($this->state->scope['foo'], $var);
    }

    /**
     * @covers ::__construct
     * @covers ::findVariable
     * @uses \ReckiCT\Graph\Variable
     * @uses \ReckiCT\Type
     */
    public function testFindVariableAstVariable()
    {
        $var = $this->state->findVariable(new AstVariable('abc'));
        $this->assertInstanceOf(JitVariable::class, $var);
        $this->assertSame($this->state->scope['abc'], $var);
    }

    /**
     * @covers ::findVariable
     * @expectedException LogicException
     */
    public function testFindVariableNonString()
    {
        $var = $this->state->findVariable(new \StdClass());
    }

    /**
     * @covers ::__construct
     * @covers ::addVertex
     */
    public function testAddVertexLonesome()
    {
        $var = $this->state->addVertex($a = new DummyVertex());

        $this->assertFalse($this->state->graph->hasVertex($a));
        $this->assertSame($var, $a);
        $this->assertSame($var, $this->state->last);
        $this->assertEquals(0, $this->state->graph->size());
    }

    /**
     * @covers ::__construct
     * @covers ::addVertex
     */
    public function testAddVertexPair()
    {
        $a = new DummyVertex();
        $this->state->last = $b = new DummyVertex();

        $var = $this->state->addVertex($a);
        $this->assertTrue($this->state->graph->hasVertex($a));
        $this->assertTrue($this->state->graph->hasVertex($b));
        $this->assertSame($var, $a);
        $this->assertSame($var, $this->state->last);
    }

}
