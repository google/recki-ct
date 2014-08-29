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

use ReckiCT\Type;

/**
 * @coversDefaultClass \ReckiCT\Graph\Variable
 */
class VariableTest extends TestCase
{
    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $r = new \ReflectionProperty(Variable::class, 'ctr');
        $r->setAccessible(true);
        $r->setValue(0);
        $a = new Variable();
        $this->assertEquals('unknown_1', (string) $a);
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     */
    public function testDefaultConstruct()
    {
        $variable = new Variable();
        $this->assertEquals(new Type(Type::TYPE_UNKNOWN), $variable->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     */
    public function testConstructWithType()
    {
        $variable = new Variable(new Type(Type::TYPE_LONG));
        $this->assertEquals(new Type(Type::TYPE_LONG), $variable->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     * @covers ::setType
     */
    public function testSetType()
    {
        $variable = new Variable();
        $this->assertEquals(new Type(Type::TYPE_UNKNOWN), $variable->getType());
        $variable->setType(new Type(Type::TYPE_LONG));
        $this->assertEquals(new Type(Type::TYPE_LONG), $variable->getType());
    }

    /**
     * @covers ::isConstant
     */
    public function testIsConstant()
    {
        $variable = new Variable();
        $this->assertFalse($variable->isConstant());
    }

}
