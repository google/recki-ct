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
 * @package Compiler
 */

namespace ReckiCT\Compiler;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \ReckiCT\Compiler\Compiler
 */
class CompilerTest extends TestCase
{
    protected $compiler;

    protected function setUp()
    {
        $this->compiler = $this->getMock(Compiler::class, ['convertToCallable']);
    }

    /**
     * @covers ::compile
     * @covers ::parseIr
     * @covers ::separateIrToFunctions
     */
    public function testSingleFunction()
    {
        $ir = <<<'EOF'
function test long
begin
a 1 2
b 3 4
end
EOF;
        $this->compiler->expects($this->once())
            ->method('convertToCallable')
            ->with($this->equalTo([
                ['function', 'test', 'long'],
                ['begin'],
                ['a', '1', '2'],
                ['b', '3', '4'],
            ]))
            ->will($this->returnValue($ret = new \StdClass()));
        $this->assertSame($ret, $this->compiler->compile($ir));
    }

    /**
     * @covers ::compile
     * @covers ::parseIr
     * @covers ::separateIrToFunctions
     */
    public function testDouble()
    {
        $ir = <<<'EOF'
function test long
begin
a 1 2
end
function test2 long
begin
a 5 6
end
EOF;
        $this->compiler->expects($this->exactly(2))
            ->method('convertToCallable')
            ->withConsecutive(
                [$this->equalTo([
                    ['function', 'test', 'long'],
                    ['begin'],
                    ['a', '1', '2'],
                ])],
                [$this->equalTo([
                    ['function', 'test2', 'long'],
                    ['begin'],
                    ['a', '5', '6'],
                ])]
            )
            ->will($this->onConsecutiveCalls(
                $r1 = new \StdClass(),
                $r2 = new \StdClass()
            ));
        $this->assertSame(['test' => $r1, 'test2' => $r2], $this->compiler->compile($ir));
    }
}
