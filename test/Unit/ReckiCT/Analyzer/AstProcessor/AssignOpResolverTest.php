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
 * @subpackage AstProcessor
 */

namespace ReckiCT\Analyzer\AstProcessor;

use PHPUnit_Framework_TestCase as TestCase;
use PhpParser\NodeTraverser;

use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\AstProcessor\AssignOpResolver
 */
class AssignOpResolverTest extends TestCase
{
    protected $traverser;

    public static function provideOperations()
    {
        return [
            ['BitwiseAnd'],
            ['BitwiseOr'],
            ['BitwiseXor'],
            ['Concat'],
            ['Div'],
            ['Minus'],
            ['Mod'],
            ['Mul'],
            ['Plus'],
            ['Pow'],
            ['ShiftLeft'],
            ['ShiftRight'],
        ];
    }

    protected function setUp()
    {
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new AssignOpResolver());
    }

    /**
     * @dataProvider provideOperations
     * @covers ::enterNode
     */
    public function testDefinedType($type)
    {
        $fromClass = 'PhpParser\Node\Expr\AssignOp\\' . $type;
        $from = new $fromClass(
            new Variable('a'),
            new Variable('b')
        );
        $toClass = 'PhpParser\Node\Expr\BinaryOp\\' . $type;
        $to = new Assign(
            new Variable('a'),
            new $toClass(
                new Variable('a'),
                new Variable('b')
            )
        );
        $this->assertEquals([$to], $this->traverser->traverse([$from]));
    }

    /**
     * @covers ::enterNode
     */
    public function testUndefinedType()
    {
        $code = new Assign(
            new Variable('a'),
            new Plus(
                new Variable('a'),
                new Variable('b')
            )
        );
        $this->assertEquals([$code], $this->traverser->traverse([$code]));
    }

}
