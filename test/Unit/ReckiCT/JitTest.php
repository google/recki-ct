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

use JITFU\Func as JITFUFunc;

/**
 * @coversDefaultClass \ReckiCT\Jit
 */
class JitTest extends TestCase
{

    /**
     * @covers ::JitFu
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     * @covers ::getFunctionGraph
     * @covers ::compileFunctionJitFu
     * @covers ::parseFile
     * @covers ::findFunction
     */
    public function testIntegration()
    {
        $this->assertInstanceOf(JITFUFunc::class, Jit::JitFu(__NAMESPACE__ . '\\test'));
    }

    /**
     * @covers ::JitFu
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     * @covers ::getFunctionGraph
     * @covers ::compileFunctionJitFu
     * @covers ::parseFile
     * @covers ::findFunction
     */
    public function testIntegration2()
    {
        $this->assertInstanceOf(JITFUFunc::class, Jit::JitFu(__NAMESPACE__ . '\\test2'));
    }

    /**
     * @covers ::JitFu
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     * @covers ::getFunctionGraph
     * @covers ::compileFunctionJitFu
     * @covers ::parseFile
     * @covers ::findFunction
     */
    public function testIntegrationNonExistantFunction()
    {
        $this->assertEquals(__NAMESPACE__ . '\\doesntExist', Jit::JitFu(__NAMESPACE__ . '\\doesntExist'));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::findFunction
     */
    public function testfindFunction()
    {
        $jit = Jit::getInstance();
        $this->assertNull($jit->findFunction('foo', ['test']));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::findFunction
     */
    public function testfindFunctionEmptyAst()
    {
        $jit = Jit::getInstance();
        $this->assertNull($jit->findFunction('foo', []));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     */
    public function testGetFunctionAstInternalFunction()
    {
        $jit = Jit::getInstance();
        $this->assertNull($jit->getFunctionAst('strlen'));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     */
    public function testGetFunctionAstNonExistantFunction()
    {
        $jit = Jit::getInstance();
        $this->assertNull($jit->getFunctionAst('foobar' . chr(0) . 'baz'));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionGraph
     */
    public function testGetFunctionGraphNonExistantFunction()
    {
        $jit = Jit::getInstance();
        $this->assertNull($jit->getFunctionGraph('foobar' . chr(0) . 'baz'));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionIr
     */
    public function testGetFunctionIrInternalFunction()
    {
        $jit = Jit::getInstance();
        $this->assertFalse($jit->getFunctionIr('strlen'));
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     * @covers ::getFunctionGraph
     * @covers ::getFunctionIr
     * @covers ::parseFile
     * @covers ::findFunction
     * @covers ::compileFunctionPHP
     *
     * @todo Fix this test, which will fix the functionality
     */
    public function testCompileFunctionPHP()
    {
        $ir = Jit::getInstance()->compileFunctionPHP(__NAMESPACE__ . '\\test');
        $expected = <<<'EOF'
function ReckiCT\test($var1) {
return $var1;
}
EOF;
        $this->assertEquals($expected, $ir);
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     * @covers ::getFunctionGraph
     * @covers ::getFunctionIr
     * @covers ::parseFile
     * @covers ::findFunction
     * @covers ::compileFunctionPHP
     */
    public function testCompileFunctionPHPNonExistantFunction()
    {
        $ir = Jit::getInstance()->compileFunctionPHP(__NAMESPACE__ . '\\doesntExist');
        $this->assertEquals('ReckiCT\doesntExist', $ir);
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::getFunctionAst
     * @covers ::getFunctionGraph
     * @covers ::getFunctionIr
     * @covers ::parseFile
     * @covers ::findFunction
     */
    public function testGetFunctionIr()
    {
        $ir = Jit::getInstance()->getFunctionIr(__NAMESPACE__ . '\\test');
        $expected = <<<'EOF'
function ReckiCT\test long
param $1 long
begin
return $1
end
EOF;
        $this->assertEquals($expected, $ir);
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::compileIrJitFu
     */
    public function testCompileIrJitFu()
    {
        $ir = <<<'EOF'
function ReckiCT\test long
param $1 long
begin
return $1
end
EOF;
        $func = Jit::getInstance()->compileIrJitFu($ir, __NAMESPACE__ . '\\test');

        $this->assertInstanceOf(JITFUFunc::class, $func);
    }

    /**
     * @covers ::getInstance
     * @covers ::__construct
     * @covers ::compileIrJitFu
     */
    public function testCompileIrJitFuWithoutExt()
    {

        $ir = <<<'EOF'
function ReckiCT\test long
param $1 long
begin
return $1
end
EOF;
        $jit = Jit::getInstance();
        $r = new \ReflectionProperty($jit, 'jitfucompiler');
        $r->setAccessible(true);
        $r->setValue($jit, null);

        $func = $jit->compileIrJitFu($ir, __NAMESPACE__ . '\\test');

        $this->assertEquals(__NAMESPACE__ . '\\test', $func);
    }
}

/**
 * @param int
 * @return int
 */
function test($a)
{
    return $a;
}

/**
 * @param string
 * @return string
 */
function test2($a)
{
    return $a;
}
