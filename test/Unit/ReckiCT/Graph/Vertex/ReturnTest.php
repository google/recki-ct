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
 * @coversDefaultClass \ReckiCT\Graph\Vertex\Return_
 */
class ReturnTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new Return_(new Variable());
        $this->assertEquals('return unknown_1', (string) $a);
    }

    /**
     * @covers ::__construct
     * @covers ::getVariables
     * @covers ::getValue
     */
    public function testBasic()
    {
        $vertex = new Return_($b = new Variable());
        $this->assertSame([$b], $vertex->getVariables());
        $this->assertSame($b, $vertex->getValue());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testReplaceVaraibleValue()
    {
        $vertex = new Return_($b = new Variable());
        $this->assertSame([$b], $vertex->getVariables());
        $vertex->replaceVariable($b, $new = new Variable());
        $this->assertSame([$new], $vertex->getVariables());
    }

    /**
     * @covers ::getVariables
     * @covers ::replaceVariable
     */
    public function testReplaceVaraibleNothing()
    {
        $vertex = new Return_($b = new Variable());
        $this->assertSame([$b], $vertex->getVariables());
        $vertex->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$b], $vertex->getVariables());
    }

}
