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
 * @package Intermediary
 */

namespace ReckiCT\Intermediary;

use PHPUnit_Framework_TestCase as TestCase;

use ReckiCT\Graph\Constant;
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Vertex;

use ReckiCT\Signature;
use ReckiCT\Type;

use Gliph\Graph\DirectedAdjacencyList;

/**
 * @coversDefaultClass \ReckiCT\Intermediary\Generator
 */
class GeneratorTest extends TestCase
{
    /**
     * @covers ::makeLabel
     */
    public function testMakeLabel()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->labels = new \SplObjectStorage();
        $state->labelidx = 0;
        $noop = new Vertex\NoOp();

        $this->assertEquals('@1', $generator->makeLabel($noop, $state));
        $this->assertEquals('@1', $generator->makeLabel($noop, $state));
        $this->assertEquals('@2', $generator->makeLabel(new Vertex\NoOp(), $state));
        $this->assertEquals('@1', $generator->makeLabel($noop, $state));
    }

    /**
     * @covers ::getVarStub
     */
    public function testGetVarStub()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->constants = [];
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = $this->getMock(Vertex::class);
        $vertex->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([
                $a = $this->getMock(Variable::class, [], [], '', false),
                $b = $this->getMock(Constant::class, [], [], '', false),
                $c = $this->getMock(Constant::class, [], [], '', false),
                $a
            ]));
        $a->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('long'));

        $b->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('double'));
        $b->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(1.5));

        $c->expects($this->exactly(2))
            ->method('getType')
            ->will($this->returnValue('string'));
        $c->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('this is a test'));

        list($out, $stub) = $generator->getVarStub($vertex, $state);
        $expected = <<<'EOF'
var $1 long

EOF;
        $constants = [
            'const $2 double 1.5',
            'const $3 string dGhpcyBpcyBhIHRlc3Q='
        ];
        $this->assertEquals($expected, $stub);
        $this->assertEquals($constants, $state->constants);
        $this->assertEquals(' $1 $2 $3 $1', $out);

    }

    /**
     * @covers ::getVarStub
     */
    public function testGetVarStubForFunction()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = $this->getMock(Vertex\Function_::class, [], [], '', false);
        $vertex->expects($this->once())
            ->method('getVariables')
            ->will($this->returnValue([
                $a = $this->getMock(Variable::class, [], [], '', false),
                $b = $this->getMock(Variable::class, [], [], '', false)
            ]));
        $a->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('long'));

        $b->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('double'));

        list($out, $stub) = $generator->getVarStub($vertex, $state);
        $expected = <<<'EOF'
param $1 long
param $2 double

EOF;
        $this->assertEquals($expected, $stub);
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForBinaryOp()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = new Vertex\BinaryOp(Vertex\BinaryOp::PLUS, new Variable(), new Variable(), new Variable());
        $expected = <<<'EOF'
var $1 unknown
var $2 unknown
var $3 unknown
+ $1 $2 $3
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForBooleanNot()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = new Vertex\BooleanNot(new Variable(), new Variable());
        $expected = <<<'EOF'
var $1 unknown
var $2 unknown
! $1 $2
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForBitwiseNot()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = new Vertex\BitwiseNot(new Variable(), new Variable());
        $expected = <<<'EOF'
var $1 unknown
var $2 unknown
~ $1 $2
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForNoOp()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->labels = new \SplObjectStorage();
        $state->labelidx = 1;
        $vertex = new Vertex\NoOp();
        $expected = <<<'EOF'
label @2
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForFunction()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = new Vertex\Function_([new Variable(), new Variable()], new Type(Type::TYPE_LONG), $this->getMock(DirectedAdjacencyList::class));
        $expected = <<<'EOF'
param $1 unknown
param $2 unknown
begin
--constants--
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForFunctionCall()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = new Vertex\FunctionCall('foo', [new Variable(), new Variable()], new Variable(new Type(Type::TYPE_LONG)), new Signature(new Type(Type::TYPE_LONG), []));
        $expected = <<<'EOF'
var $1 unknown
var $2 unknown
var $3 long
functioncall foo $1 $2 $3
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForFunctionCallRecursive()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->scope = new \SplObjectStorage();
        $state->varidx = 0;
        $vertex = new Vertex\FunctionCall('foo', [new Variable(), new Variable()], new Variable(new Type(Type::TYPE_LONG)), new Signature(new Type(Type::TYPE_LONG), []), true);
        $expected = <<<'EOF'
var $1 unknown
var $2 unknown
var $3 long
recurse $1 $2 $3
EOF;
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForEnd()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $vertex = new Vertex\End();
        $expected = 'return';
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::generate
     */
    public function testGenerateWithSeenVertex()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $vertex = new Vertex\End();
        $state->seen = new \SplObjectStorage();
        $state->seen[$vertex] = true;
        $expected = '';
        $this->assertEquals($expected, $generator->generate($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForJumpWithSeenNoOp()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->seen = new \SplObjectStorage();
        $state->graph = new DirectedAdjacencyList();
        $noOp = new Vertex\NoOp();
        $state->seen[$noOp] = true;

        $state->labels = new \SplObjectStorage();
        $state->labelidx = 1;

        $vertex = new Vertex\Jump();
        $state->graph->ensureArc($vertex, $noOp);

        $expected = 'jump @2';
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::getTypedOutput
     */
    public function testGetTypedOutputForJumpWithoutSeenNoOp()
    {
        $generator = new Generator();
        $state = new \StdClass();
        $state->seen = new \SplObjectStorage();
        $state->graph = new DirectedAdjacencyList();
        $noOp = new Vertex\NoOp();

        $state->labels = new \SplObjectStorage();
        $state->labelidx = 1;

        $vertex = new Vertex\Jump();
        $state->graph->ensureArc($vertex, $noOp);

        $expected = '';
        $this->assertEquals($expected, $generator->getTypedOutput($vertex, $state));
    }

    /**
     * @covers ::generateFunction
     * @covers ::generate
     */
    public function testGenerateFunction()
    {
        $generator = new Generator();

        $graph = new DirectedAdjacencyList();
        $long = new Type(Type::TYPE_LONG);
        $double = new Type(Type::TYPE_DOUBLE);
        $func = new Vertex\Function_([$a = new Variable($long), $b = new Variable($double)], $double, $graph);
        $noOp = new Vertex\NoOp();
        $jumpz = new Vertex\JumpZ($noOp, $a);
        $end = new Vertex\End();
        $graph->ensureArc($func, $jumpz);
        $graph->ensureArc($jumpz, $noOp);
        $graph->ensureArc($jumpz, $r1 = new Vertex\Return_(new Constant(1.5)));
        $graph->ensureArc($r1, $end);
        $graph->ensureArc($noOp, $r2 = new Vertex\Return_($b));
        $graph->ensureArc($r2, $end);

        $expected = <<<'EOF'
function test123 double
param $1 long
param $2 double
begin
const $3 double 1.5
jumpz $1 @1
return $3
label @1
return $2
endfunction
EOF;
        $this->assertEquals($expected, $generator->generateFunction('test123', $func));
    }

    /**
     * @covers ::generateFunction
     * @covers ::generate
     */
    public function testGenerateFunctionReversedJumpZ()
    {
        $generator = new Generator();

        $graph = new DirectedAdjacencyList();
        $long = new Type(Type::TYPE_LONG);
        $double = new Type(Type::TYPE_DOUBLE);
        $func = new Vertex\Function_([$a = new Variable($long), $b = new Variable($double)], $double, $graph);
        $noOp = new Vertex\NoOp();
        $jumpz = new Vertex\JumpZ($noOp, $a);
        $end = new Vertex\End();
        $graph->ensureArc($func, $jumpz);
        $graph->ensureArc($jumpz, $r1 = new Vertex\Return_(new Constant(1.5)));
        $graph->ensureArc($jumpz, $noOp);
        $graph->ensureArc($r1, $end);
        $graph->ensureArc($noOp, $r2 = new Vertex\Return_($b));
        $graph->ensureArc($r2, $end);

        $expected = <<<'EOF'
function test123 double
param $1 long
param $2 double
begin
const $3 double 1.5
jumpz $1 @1
return $3
label @1
return $2
endfunction
EOF;
        $this->assertEquals($expected, $generator->generateFunction('test123', $func));
    }

}
