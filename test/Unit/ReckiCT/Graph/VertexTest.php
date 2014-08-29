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
 */

namespace ReckiCT\Graph;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \ReckiCT\Graph\Vertex
 */
class VertexTest extends TestCase
{
    /**
     * @covers ::getName
     */
    public function testGetName()
    {
        $vertex = new Stub_Vertex();
        $this->assertEquals('StubVertex', $vertex->getName());
    }

    /**
     * @covers ::hasVariable
     */
    public function testHasVariableWithoutHavingIt()
    {
        $vertex = new Stub_Vertex([new Variable(), new Variable()]);
        $this->assertFalse($vertex->hasVariable(new Variable()));
    }

    /**
     * @covers ::hasVariable
     */
    public function testHasVariableWithHavingIt()
    {
        $vertex = new Stub_Vertex([$a = new Variable(), new Variable()]);
        $this->assertTrue($vertex->hasVariable($a));
    }

}

class Stub_Vertex extends Vertex
{
    protected $variables = [];
    public function __construct(array $variables = [])
    {
        $this->variables = $variables;
    }
    public function __toString()
    {
        return '';
    }
    public function getVariables($includePhi = false)
    {
        return $this->variables;
    }
    public function replaceVariable(Variable $from, Variable $to) {}
}
