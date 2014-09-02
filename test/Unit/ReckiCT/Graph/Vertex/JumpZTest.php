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

use ReckiCT\Graph\Vertex;
use PHPUnit_Framework_TestCase as TestCase;
use ReckiCT\Graph\Variable;

/**
 * @coversDefaultClass \ReckiCT\Graph\Vertex\JumpZ
 */
class JumpZTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new JumpZ($this->getMock(Vertex::class), new Variable());
        $this->assertEquals('jumpz(unknown_1)', (string) $a);
    }

    /**
     * @covers ::__construct
     * @covers ::getVariables
     * @covers ::getValue
     * @covers ::getTarget
     */
    public function testBasic()
    {
        $vertex = new JumpZ($a = $this->getMock(Vertex::class), $b = new Variable());
        $this->assertSame([$b], $vertex->getVariables());
        $this->assertSame($b, $vertex->getValue());
        $this->assertSame($a, $vertex->getTarget());
    }

    /**
     * @covers ::setTarget
     * @covers ::getTarget
     */
    public function testSetTarget()
    {
        $vertex = new JumpZ($a = $this->getMock(Vertex::class), $b = new Variable());
        $this->assertSame($a, $vertex->getTarget());
        $vertex->setTarget($new = $this->getMock(Vertex::class));
        $this->assertSame($new, $vertex->getTarget());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testReplaceVariableValue()
    {
        $vertex = new JumpZ($this->getMock(Vertex::class), $b = new Variable());
        $this->assertSame([$b], $vertex->getVariables());
        $vertex->replaceVariable($b, $new = new Variable());
        $this->assertSame([$new], $vertex->getVariables());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testReplaceVariableNothing()
    {
        $vertex = new JumpZ($this->getMock(Vertex::class), $b = new Variable());
        $this->assertSame([$b], $vertex->getVariables());
        $vertex->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$b], $vertex->getVariables());
    }
}
