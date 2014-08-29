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
 * @coversDefaultClass \ReckiCT\Graph\Constant
 */
class ConstantTest extends TestCase
{
    /**
     * @covers ::__toString()
     */
    public function testToString()
    {
        $constant = new Constant(10.0);
        $this->assertEquals('numeric(10)', (string) $constant);
    }

    /**
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testInteger()
    {
        $constant = new Constant(1);
        $this->assertEquals(1, $constant->getValue());
        $this->assertEquals(new Type(Type::TYPE_NUMERIC), $constant->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testDouble()
    {
        $constant = new Constant(1.5);
        $this->assertEquals(1.5, $constant->getValue());
        $this->assertEquals(new Type(Type::TYPE_DOUBLE), $constant->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testDoubleRepresentableAsInteger()
    {
        $constant = new Constant(1.0);
        $this->assertEquals(1.0, $constant->getValue());
        $this->assertEquals(new Type(Type::TYPE_NUMERIC), $constant->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testString()
    {
        $constant = new Constant("Foo");
        $this->assertEquals("Foo", $constant->getValue());
        $this->assertEquals(new Type(Type::TYPE_STRING), $constant->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getValue
     */
    public function testBoolean()
    {
        $constant = new Constant(true);
        $this->assertEquals(true, $constant->getValue());
        $this->assertEquals(new Type(Type::TYPE_BOOLEAN), $constant->getType());
    }

    /**
     * @covers ::__construct
     * @expectedException RuntimeException
     */
    public function testInvalidConstant()
    {
        $constant = new Constant([]);
    }
}
