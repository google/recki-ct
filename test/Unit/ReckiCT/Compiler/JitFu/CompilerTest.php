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
 * @subpackage JitFu
 */

namespace ReckiCT\Compiler\JitFu;

use PHPUnit_Framework_TestCase as TestCase;

use JITFU\Signature as JitSignature;
use JITFU\Context as JitContext;
use JITFU\Func as JitFunc;
use JITFU\Type as JitType;
use JITFU\Label as JitLabel;
use JITFU\Value as JitValue;

use ReckiCT\Jit;

/**
 * @coversDefaultClass \ReckiCT\Compiler\JitFu\Compiler
 */
class CompilerTest extends TestCase
{
    protected $compiler;
    protected $ctx;
    protected $jit;
    protected $jitcontext;
    protected $signature;

    /**
     * @covers ::__construct
     */
    protected function setUp()
    {
        $this->jit = $this->getMock(Jit::class, [], [], '', false);
        $this->compiler = new Compiler($this->jit);
        $this->jitcontext = new JitContext();
        $this->signature = new JitSignature(JitType::of(JitType::void), []);
        $this->ctx = new \StdClass();
        $this->ctx->scope = [];
        $this->ctx->compiler = $this->compiler;
        $this->ctx->instructions = [];
        $this->ctx->i = 0;
        $this->ctx->argNames = [];
        $this->ctx->labels = [];
        $this->ctx->function = $this->getMock(JitFunc::class, [
                'doStore',
                'doLabel',
                'doBranch',
                'doBranchIfNot',
                'doDefaultReturn',
                'doReturn',
                'doToNotBool',
                'doXor',
                'doAdd',
                'doCall',
            ], [
                $this->jitcontext,
                $this->signature
            ]);
    }

    public static function provideTestCreateType()
    {
        return [
            ['bool', JitType::of(JitType::long)],
            ['numeric', JitType::of(JitType::long)],
            ['long', JitType::of(JitType::long)],
            ['double', JitType::of(JitType::double)],
            ['string', JitType::of(JitType::string)],
            ['void', JitType::of(JitType::void)],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::createType
     * @dataProvider provideTestCreateType
     */
    public function testCreateType($type, $expected)
    {
        $this->assertEquals($expected, $this->compiler->createType($type));
    }

    /**
     * @covers ::__construct
     * @covers ::createType
     * @expectedException RuntimeException
     */
    public function testCreateTypeFailure()
    {
        $this->compiler->createType('');
    }

    /**
     * @covers ::__construct
     * @covers ::storeResultVar
     */
    public function testStoreResultVarWhereVarIsUsed()
    {
        $var = '$1';
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope[$var] = $v1;
        $this->ctx->instructions[$this->ctx->i - 1] = ['foo'];
        $this->ctx->function->expects($this->once())
            ->method('doStore')
            ->with($this->identicalTo($v1), $this->identicalTo($v2));
        $this->compiler->storeResultVar($var, $v2, $this->ctx);
        $this->assertSame($v1, $this->ctx->scope[$var]);
    }

    /**
     * @covers ::__construct
     * @covers ::storeResultVar
     */
    public function testStoreResultVarWhereVarIsNotUsed()
    {
        $var = '$1';
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope[$var] = $v1;
        $this->ctx->instructions[$this->ctx->i - 1] = ['var', '$1'];
        $this->ctx->function->expects($this->never())
            ->method('doStore');
        $this->compiler->storeResultVar($var, $v2, $this->ctx);
        $this->assertSame($v2, $this->ctx->scope[$var]);
    }

    /**
     * @covers ::__construct
     * @covers ::convertToCallable
     */
    public function testConvertToCallable()
    {
        $instructions = [
            ['function', 'foo', 'long'],
            ['param', '$1', 'long'],
            ['begin'],
            ['return', '$1'],
        ];
        $func = $this->compiler->convertToCallable($instructions);
        $this->assertInstanceOf(JitFunc::class, $func);
        $f2 = $this->compiler->convertToCallable($instructions);
        $this->assertSame($func, $f2);
    }

    /**
     * @covers ::__construct
     * @covers ::compileInstruction
     * @expectedException RuntimeException
     */
    public function testCompileInstructionInvalid()
    {
        $instruction = ['invalid'];
        $this->compiler->compileInstruction($instruction, $this->ctx);
    }

    /**
     * @covers ::__construct
     * @covers ::compileInstruction
     */
    public function testCompileInstructionConst()
    {
        $instruction = ['const', '$1', 'long', '12'];
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertInstanceOf(JitValue::class, $this->ctx->scope['$1']);
    }

    /**
     * @covers ::__construct
     * @covers ::compileInstruction
     * @expectedException RuntimeException
     */
    public function testCompileInstructionConstFailure()
    {
        $instruction = ['const', '$1', 'foobar', '12'];
        $this->compiler->compileInstruction($instruction, $this->ctx);
    }

    /**
     * @covers ::__construct
     * @covers ::compileInstruction
     */
    public function testCompileInstructionConstDouble()
    {
        $instruction = ['const', '$1', 'double', '12.5'];
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertInstanceOf(JitValue::class, $this->ctx->scope['$1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionConstString()
    {
        $instruction = ['const', '$1', 'string', base64_encode("this is a test")];
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertInstanceOf(JitValue::class, $this->ctx->scope['$1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionVar()
    {
        $instruction = ['var', '$1', 'long'];
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertInstanceOf(JitValue::class, $this->ctx->scope['$1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionAssign()
    {
        $instruction = ['assign', '$1', '$2'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
            '$2' => $v2,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doStore')
            ->with($this->identicalTo($v2), $this->identicalTo($v1));
        $this->compiler->compileInstruction($instruction, $this->ctx);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionReturn()
    {
        $instruction = ['return', '$1'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doReturn')
            ->with($this->identicalTo($v1));
        $this->compiler->compileInstruction($instruction, $this->ctx);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionDefaultReturn()
    {
        $instruction = ['return'];
        $this->ctx->function->expects($this->once())
            ->method('doDefaultReturn');
        $this->compiler->compileInstruction($instruction, $this->ctx);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionLabelWithout()
    {
        $instruction = ['label', '@1'];
        $r = $this->getMock(JitLabel::class, [], [], '', false);
        $this->ctx->function->expects($this->once())
            ->method('doLabel')
            ->with()
            ->will($this->returnValue($r));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->labels['@1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionLabelWith()
    {
        $instruction = ['label', '@1'];
        $this->ctx->labels['@1'] = $r = $this->getMock(JitLabel::class, [], [], '', false);

        $this->ctx->function->expects($this->once())
            ->method('doLabel')
            ->with($this->identicalTo($r))
            ->will($this->returnValue(1));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->labels['@1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionJumpWithOut()
    {
        $instruction = ['jump', '@1'];
        $r = $this->getMock(JitLabel::class, [], [], '', false);
        $this->ctx->function->expects($this->once())
            ->method('doBranch')
            ->with()
            ->will($this->returnValue($r));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->labels['@1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionJumpWith()
    {
        $instruction = ['jump', '@1'];
        $this->ctx->labels['@1'] = $r = $this->getMock(JitLabel::class, [], [], '', false);
        $this->ctx->function->expects($this->once())
            ->method('doBranch')
            ->with($this->identicalTo($r))
            ->will($this->returnValue(1));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->labels['@1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionJumpZWithOut()
    {
        $instruction = ['jumpz', '$1', '@1'];
        $r = $this->getMock(JitLabel::class, [], [], '', false);
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doBranchIfNot')
            ->with($this->identicalTo($v1))
            ->will($this->returnValue($r));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->labels['@1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionJumpZWith()
    {
        $instruction = ['jumpz', '$1', '@1'];
        $this->ctx->labels['@1'] = $r = $this->getMock(JitLabel::class, [], [], '', false);
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doBranchIfNot')
            ->with($this->identicalTo($v1), $this->identicalTo($r))
            ->will($this->returnValue(1));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->labels['@1']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionBooleanNot()
    {
        $this->ctx->instructions[$this->ctx->i - 1] = ['var', '$2'];
        $instruction = ['!', '$1', '$2'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $r = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
            '$2' => $v2,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doToNotBool')
            ->with($this->identicalTo($v1))
            ->will($this->returnValue($r));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->scope['$2']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionBitwiseNot()
    {
        $this->ctx->instructions[$this->ctx->i - 1] = ['var', '$2'];
        $instruction = ['~', '$1', '$2'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $neg = $this->getMock(JitValue::class, [], [], '', false);
        $r = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
            '$2' => $v2,
            -1 => $neg,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doXor')
            ->with($this->identicalTo($v1), $this->identicalTo($neg))
            ->will($this->returnValue($r));
        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->scope['$2']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionBinaryOp()
    {
        $this->ctx->instructions[$this->ctx->i - 1] = ['var', '$3'];
        $instruction = ['+', '$1', '$2', '$3'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $v3 = $this->getMock(JitValue::class, [], [], '', false);
        $r = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
            '$2' => $v2,
            '$3' => $v3,
        ];
        $this->ctx->function->expects($this->once())
            ->method('doAdd')
            ->with($this->identicalTo($v1), $this->identicalTo($v2))
            ->will($this->returnValue($r));

        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->scope['$3']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionRecurse()
    {
        $this->ctx->instructions[$this->ctx->i - 1] = ['var', '$3'];
        $instruction = ['recurse', '$1', '$2', '$3'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $v3 = $this->getMock(JitValue::class, [], [], '', false);
        $r = $this->getMock(JitValue::class, [], [], '', false);
        $this->ctx->scope = [
            '$1' => $v1,
            '$2' => $v2,
            '$3' => $v3
        ];
        $this->ctx->function->expects($this->once())
            ->method('doCall')
            ->with($this->identicalTo($this->ctx->function), $this->identicalTo([$v1, $v2]))
            ->will($this->returnValue($r));

        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->scope['$3']);
    }

    /**
     * @covers ::compileInstruction
     */
    public function testCompileInstructionFunctionCall()
    {
        $this->ctx->instructions[$this->ctx->i - 1] = ['var', '$3'];
        $instruction = ['functioncall', 'foo', '$1', '$2', '$3'];
        $v1 = $this->getMock(JitValue::class, [], [], '', false);
        $v2 = $this->getMock(JitValue::class, [], [], '', false);
        $v3 = $this->getMock(JitValue::class, [], [], '', false);
        $r = $this->getMock(JitValue::class, [], [], '', false);

        $this->ctx->scope = [
            '$1' => $v1,
            '$2' => $v2,
            '$3' => $v3
        ];
        $this->jit->expects($this->once())
            ->method('compileFunctionJitFu')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue($f = $this->getMock(JitFunc::class, [], [
                $this->jitcontext,
                $this->signature
            ])));

        $this->ctx->function->expects($this->once())
            ->method('doCall')
            ->with($this->identicalTo($f), $this->identicalTo([$v1, $v2]))
            ->will($this->returnValue($r));

        $this->compiler->compileInstruction($instruction, $this->ctx);
        $this->assertSame($r, $this->ctx->scope['$3']);
    }

}
