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
 * @package Analyzer
 */

namespace ReckiCT\Analyzer;

use PHPUnit_Framework_TestCase as TestCase;

use ReckiCT\Type;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\Function_;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\SignatureResolver
 */
class SignatureResolverTest extends TestCase
{
    protected $resolver;

    public function setUp()
    {
        $this->resolver = new SignatureResolver();
    }

    public static function provideBuiltin()
    {
        return [
            ['count', 'int', ['array']],
            ['pow', 'float', ['numeric', 'numeric']],
            ['strlen', 'int', ['string']],
        ];
    }

    /**
     * @covers ::resolve
     * @covers ::resolveSignature
     */
    public function testResolveAst()
    {
        $func = new Function_(
            'foo',
            [],
            [
                'comments' => [
                    new Doc('/**
                              * @param int
                              * @param foo
                              * @return bar
                              */')
                ]
            ]
        );
        $signature = $this->resolver->resolve($func);
        $this->assertEquals(new Type(Type::TYPE_USER, null, '\bar'), $signature->getReturn());
        $this->assertEquals(2, count($signature->getParams()));
        $this->assertEquals(new Type(Type::TYPE_LONG), $signature->getParam(0));
        $this->assertEquals(new Type(Type::TYPE_USER, null, '\foo'), $signature->getParam(1));
    }

    /**
     * @dataProvider provideBuiltin
     * @covers ::resolve
     */
    public function testResolveBuiltin($func, $return, array $params)
    {
        $signature = $this->resolver->resolve($func);

        $this->assertEquals(Type::normalizeType($return), $signature->getReturn());

        $this->assertEquals(count($params), count($signature->getParams()));

        foreach ($params as $key => $param) {
            $this->assertEquals(Type::normalizeType($param), $signature->getParam($key));
        }
    }

    /**
     * @covers ::resolve
     * @covers ::resolveSignature
     */
    public function testResolveExternal()
    {
        $signature = $this->resolver->resolve(__NAMESPACE__ . '\mockSignatureFunction');
        $this->assertEquals(new Type(Type::TYPE_STRING), $signature->getReturn());
        $this->assertEquals(1, count($signature->getParams()));
        $this->assertEquals(new Type(Type::TYPE_LONG), $signature->getParam(0));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveInternal()
    {
        $signature = $this->resolver->resolve('assert');
        $this->assertEquals(new Type(Type::TYPE_UNKNOWN), $signature->getReturn());
        $this->assertEquals(0, count($signature->getParams()));
    }

    /**
     * @covers ::resolveSignature
     */
    public function testResolveSignature()
    {
        $signature = $this->resolver->resolveSignature('/***/');
        $this->assertEquals(new Type(Type::TYPE_UNKNOWN), $signature->getReturn());
        $this->assertEquals(0, count($signature->getParams()));
    }

}

/**
 * @param int
 * @return string
 */
function mockSignatureFunction($int)
{
    return "test";
}
