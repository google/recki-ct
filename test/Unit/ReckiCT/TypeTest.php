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
 * @package Main
 */

namespace ReckiCT;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \ReckiCT\Type
 */
class TypeTest extends TestCase
{
    public static function provideToStringTest()
    {
        return [
            [new Type(Type::TYPE_UNKNOWN), 'unknown'],
            [new Type(Type::TYPE_VOID), 'void'],
            [new Type(Type::TYPE_LONG), 'long'],
            [new Type(Type::TYPE_DOUBLE), 'double'],
            [new Type(Type::TYPE_NUMERIC), 'numeric'],
            [new Type(Type::TYPE_STRING), 'string'],
            [new Type(Type::TYPE_BOOLEAN), 'bool'],
            [new Type(Type::TYPE_NULL), 'null'],
            [new Type(Type::TYPE_ZVAL), 'mixed'],
            [new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_UNKNOWN)), 'unknown[]'],
            [new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_LONG))), 'long[][]'],
            [new Type(Type::TYPE_USER, null, 'Foo'), 'Foo'],
            [new Type(-99), ''],
        ];
    }

    public static function provideNormalizeTest()
    {
        return [
            ['bool', new Type(Type::TYPE_BOOLEAN)],
            ['boolean', new Type(Type::TYPE_BOOLEAN)],
            ['int', new Type(Type::TYPE_LONG)],
            ['integer', new Type(Type::TYPE_LONG)],
            ['long', new Type(Type::TYPE_LONG)],
            ['numeric', new Type(Type::TYPE_NUMERIC)],
            ['float', new Type(Type::TYPE_DOUBLE)],
            ['double', new Type(Type::TYPE_DOUBLE)],
            ['string', new Type(Type::TYPE_STRING)],
            ['void', new Type(Type::TYPE_VOID)],
            ['null', new Type(Type::TYPE_NULL)],
            ['NULL', new Type(Type::TYPE_NULL)],
            ['mixed', new Type(Type::TYPE_ZVAL)],
            ['array', new Type(Type::TYPE_HASH)],
            ['int[]', new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_LONG))],
            ['StdClass', new Type(Type::TYPE_USER, null, 'StdClass')],
            ['', new Type(Type::TYPE_UNKNOWN)],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     */
    public function testVoid()
    {
        $type = new Type(Type::TYPE_VOID);
        $this->assertEquals(Type::TYPE_VOID, $type->getType());
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     * @covers ::getUserType
     */
    public function testUser()
    {
        $type = new Type(Type::TYPE_USER, null, 'StdClass');
        $this->assertEquals(Type::TYPE_USER, $type->getType());
        $this->assertEquals('StdClass', $type->getUserType());
    }

    /**
     * @covers ::__construct
     * @expectedException InvalidArgumentException
     */
    public function testUserWithoutUserTypeProvided()
    {
        $type = new Type(Type::TYPE_USER);
    }

    /**
     * @covers ::__construct
     * @expectedException InvalidArgumentException
     */
    public function testPassingUserTypeWithoutBeingUserType()
    {
        $type = new Type(Type::TYPE_VOID, null, "StdClass");
    }

    /**
     * @covers ::__construct
     * @covers ::getType
     */
    public function testComplexType()
    {
        $type = new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_UNKNOWN));
        $this->assertEquals(Type::TYPE_ARRAY, $type->getType());
    }

    /**
     * @covers ::__construct
     * @expectedException InvalidArgumentException
     */
    public function testComplexWithoutUserTypeProvided()
    {
        $type = new Type(Type::TYPE_ARRAY);
    }

    /**
     * @covers ::__construct
     * @expectedException InvalidArgumentException
     */
    public function testPassingComplexTypeWithoutBeingComplexType()
    {
        $type = new Type(Type::TYPE_VOID, new Type(Type::TYPE_UNKNOWN));
    }

    /**
     * @covers ::__toString
     * @dataProvider provideToStringTest
     */
    public function testToString($obj, $expected)
    {
        $this->assertEquals($expected, (string) $obj);
    }

    /**
     * @covers ::__construct
     * @covers ::normalizeType
     */
    public function testNormalizeTypeWithType()
    {
        $type = new Type(Type::TYPE_LONG);
        $this->assertEquals($type, Type::normalizeType($type));
    }

    /**
     * @covers ::__construct
     * @covers ::normalizeType
     */
    public function testNormalizeTypeWithSubType()
    {
        $type = new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_LONG));
        $this->assertEquals($type, Type::normalizeType('array', new Type(Type::TYPE_LONG)));
    }

    /**
     * @covers ::__construct
     * @covers ::normalizeType
     * @dataProvider provideNormalizeTest
     */
    public function testNormalizeType($type, $expected)
    {
        $this->assertEquals($expected, Type::normalizeType($type));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     * @covers ::normalizeType
     */
    public function testEqualsWithArrayAndStringArray()
    {
        $type = new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_UNKNOWN));
        $this->assertTrue($type->equals('array'));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     * @covers ::normalizeType
     */
    public function testEqualsWithStringType()
    {
        $type = new Type(Type::TYPE_LONG);
        $this->assertTrue($type->equals('int'));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     * @expectedException InvalidArgumentException
     */
    public function testEqualsWithInvalidType()
    {
        $type = new Type(Type::TYPE_LONG);
        $this->assertTrue($type->equals(42));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     * @covers ::normalizeType
     */
    public function testEqualsArray()
    {
        $type = new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_LONG));
        $this->assertTrue($type->equals('int[]'));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     */
    public function testEqualsArrayFailure()
    {
        $type = new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_LONG));
        $this->assertFalse($type->equals(new Type(Type::TYPE_LONG)));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     * @covers ::normalizeType
     */
    public function testEqualsUser()
    {
        $type = new Type(Type::TYPE_USER, null, 'StdClass');
        $this->assertTrue($type->equals('StdClass'));
    }

    /**
     * @covers ::__construct
     * @covers ::equals
     * @covers ::normalizeType
     */
    public function testEqualsUserFailure()
    {
        $type = new Type(Type::TYPE_USER, null, 'StdClass');
        $this->assertFalse($type->equals('Foo'));
    }

    /**
     * @covers ::__construct
     * @covers ::getSubType
     */
    public function testGetSubTypeNonComplex()
    {
        $type = new Type(Type::TYPE_LONG);
        $this->assertEquals($type, $type->getSubType());
    }

    /**
     * @covers ::__construct
     * @covers ::getSubType
     */
    public function testGetSubTypeNonComplex2()
    {
        $type = new Type(Type::TYPE_DOUBLE);
        $this->assertEquals($type, $type->getSubType());
    }

    /**
     * @covers ::__construct
     * @covers ::getSubType
     */
    public function testGetSubTypeComplex()
    {
        $type = new Type(Type::TYPE_ARRAY, $sub = new Type(Type::TYPE_ARRAY, new Type(Type::TYPE_LONG)));
        $this->assertEquals($sub, $type->getSubType());
    }

    /**
     * @covers ::__construct
     * @covers ::getSubType
     */
    public function testGetSubTypeComplex2()
    {
        $type = new Type(Type::TYPE_HASH);
        $this->assertEquals(new Type(Type::TYPE_ZVAL), $type->getSubType());
    }

    /**
     * @covers ::__construct
     * @covers ::isZval
     */
    public function testIsZvalWithZval()
    {
        $type = new Type(Type::TYPE_ZVAL);
        $this->assertTrue($type->isZval());
    }

    /**
     * @covers ::__construct
     * @covers ::isZval
     */
    public function testIsZvalWithoutZval()
    {
        $type = new Type(Type::TYPE_LONG);
        $this->assertFalse($type->isZval());
    }

    /**
     * @covers ::__construct
     * @covers ::isUnknown
     */
    public function testIsUnknownWithUnknown()
    {
        $type = new Type(Type::TYPE_UNKNOWN);
        $this->assertTrue($type->isUnknown());
    }

    /**
     * @covers ::__construct
     * @covers ::isUnknown
     */
    public function testIsUnknownWithoutUnknown()
    {
        $type = new Type(Type::TYPE_LONG);
        $this->assertFalse($type->isUnknown());
    }

}
