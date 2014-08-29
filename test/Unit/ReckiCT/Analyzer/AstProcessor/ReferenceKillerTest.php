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

use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Param;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\Variable;

/**
 * @coversDefaultClass ReckiCT\Analyzer\AstProcessor\ReferenceKiller
 */
class ReferenceKillerTest extends TestCase
{
    protected $traverser;

    protected function setUp()
    {
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new ReferenceKiller());
    }

    /**
     * @covers ::enterNode
     */
    public function testNormalFunction()
    {
        $from = new Function_('foo', ['stmts' => [new Variable('a')]]);
        $this->assertEquals([$from], $this->traverser->traverse([$from]));
    }

    /**
     * @expectedException LogicException
     * @covers ::enterNode
     */
    public function testKillFunction()
    {
        $this->traverser->traverse([
            new Function_('foo', ['byRef' => true]),
        ]);
    }

    /**
     * @expectedException LogicException
     * @covers ::enterNode
     */
    public function testKillFunctionParam()
    {
        $this->traverser->traverse([
            new Function_('foo', ['params' => [new Param('foo', null, null, true)]]),
        ]);
    }

    /**
     * @expectedException LogicException
     * @covers ::enterNode
     */
    public function testKillAssignByRef()
    {
        $this->traverser->traverse([
            new AssignRef(new Variable('a'), new Variable('b')),
        ]);
    }
}
