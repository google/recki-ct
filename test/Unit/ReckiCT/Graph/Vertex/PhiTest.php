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
 * @coversDefaultClass \ReckiCT\Graph\Vertex\Phi
 */
class PhiTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new Phi(new Variable());
        $a->addValue(new Variable());
        $this->assertEquals('unknown_1 = Φ(unknown_2)', (string) $a);
    }

    /**
     * @covers ::__construct
     * @covers ::addValue
     * @covers ::getValues
     * @covers ::getVariables
     */
    public function testConstruction()
    {
        $phi = new Phi(
            $r = new Variable()
        );
        $phi->addValue($a = new Variable());
        $phi->addValue($b = new Variable());
        $this->assertSame([$a, $b], iterator_to_array($phi->getValues()));
        $this->assertSame([$r, $a, $b], $phi->getVariables());
    }

    /**
     * @covers ::__construct
     * @covers ::addValue
     * @covers ::getValues
     * @covers ::getVariables
     * @covers ::removeValue
     */
    public function testRemoveValue()
    {
        $phi = new Phi(
            $r = new Variable()
        );
        $phi->addValue($a = new Variable());
        $phi->addValue($b = new Variable());
        $this->assertSame([$a, $b], iterator_to_array($phi->getValues()));
        $this->assertSame([$r, $a, $b], $phi->getVariables());
        $phi->removeValue($a);
        $this->assertSame([$b], iterator_to_array($phi->getValues()));
        $this->assertSame([$r, $b], $phi->getVariables());

    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariables()
    {
        $phi = new Phi(
            $r = new Variable()
        );
        $phi->addValue($a = new Variable());
        $phi->addValue($b = new Variable());
        $phi->replaceVariable($a, $new = new Variable());
        $this->assertSame([$r, $b, $new], $phi->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariablesResult()
    {
        $phi = new Phi(
            $r = new Variable()
        );
        $phi->addValue($a = new Variable());
        $phi->addValue($b = new Variable());
        $phi->replaceVariable($r, $new = new Variable());
        $this->assertSame([$r, $a, $b], $phi->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceVariablesNeither()
    {
        $phi = new Phi(
            $r = new Variable()
        );
        $phi->addValue($a = new Variable());
        $phi->addValue($b = new Variable());
        $phi->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$r, $a, $b], $phi->getVariables());
    }
}
