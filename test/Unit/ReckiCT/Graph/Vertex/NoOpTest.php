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

/**
 * @coversDefaultClass \ReckiCT\Graph\Vertex\NoOp
 */
class NoOpTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $a = new NoOp();
        $this->assertEquals('NoOp', (string) $a);
    }

    /**
     * @covers ::getVariables
     */
    public function testGetVariables()
    {
        $vertex = new NoOp();
        $this->assertEmpty($vertex->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariable()
    {
        $vertex = new NoOp();
        $vertex->replaceVariable(new Variable(), new Variable());
    }

}
