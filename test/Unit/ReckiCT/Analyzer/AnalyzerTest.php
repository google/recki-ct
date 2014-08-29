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

use PhpParser\Node\Stmt\Function_ as AstFunction;
use PhpParser\NodeVisitorAbstract;

use ReckiCT\Type;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;
use Gliph\Graph\DirectedAdjacencyList;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\Analyzer
 */
class AnalyzerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::addVisitor
     * @covers ::analyzeFunction
     */
    public function testAstOperation()
    {
        $func = new AstFunction('foo');
        $analyzer = new Analyzer();
        $visitor = $this->getMock(NodeVisitorAbstract::class);
        $visitor->expects($this->once())
            ->method('enterNode')
            ->with($this->identicalTo($func));
        $analyzer->addVisitor($visitor);

        $this->assertEquals($func, $analyzer->analyzeFunction($func));
    }

    /**
     * @covers ::__construct
     * @covers ::addProcessor
     * @covers ::analyzeGraph
     */
    public function testGraphOperation()
    {
        $func = new JitFunction([], new Type(0), $g = new DirectedAdjacencyList());
        $processor = $this->getMock(GraphProcessor::class);
        $processor->expects($this->once())
            ->method('process')
            ->with($this->callback(function (GraphState $state) use ($func) {
                return $func === $state->getFunction();
            }));
        $analyzer = new Analyzer();
        $analyzer->addProcessor($processor);

        $analyzer->analyzeGraph($func);
    }

}
