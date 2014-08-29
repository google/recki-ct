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
 * @package Graph
 * @subpackage Vertex
 */

namespace ReckiCT\Graph\Vertex;

use PHPUnit_Framework_TestCase as TestCase;
use ReckiCT\Graph\Variable;
use ReckiCT\Type;

use Gliph\Graph\DirectedAdjacencyList;

/**
 * @coversDefaultClass \ReckiCT\Graph\Vertex\Function_
 */
class FunctionTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new Function_([new Variable(), new Variable()], new Type(Type::TYPE_LONG), new DirectedAdjacencyList());
        $this->assertEquals('function(unknown_1, unknown_2): long', (string) $a);
    }

    /**
     * @covers ::__construct
     * @covers ::getVariables
     * @covers ::getReturnType
     * @covers ::getGraph
     */
    public function testBasic()
    {
        $vertex = new Function_([$a = new Variable()], $t = new Type(Type::TYPE_LONG), $g = new DirectedAdjacencyList());
        $this->assertSame([$a], $vertex->getVariables());
        $this->assertSame($t, $vertex->getReturnType());
        $this->assertSame($g, $vertex->getGraph());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testGetVariablesArg()
    {
        $vertex = new Function_([$a = new Variable()], new Type(Type::TYPE_LONG), new DirectedAdjacencyList());
        $vertex->replaceVariable($a, $new = new Variable());
        $this->assertSame([$new], $vertex->getVariables());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testGetVariablesNone()
    {
        $vertex = new Function_([$a = new Variable()], new Type(Type::TYPE_LONG), new DirectedAdjacencyList());
        $vertex->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$a], $vertex->getVariables());
    }
}
