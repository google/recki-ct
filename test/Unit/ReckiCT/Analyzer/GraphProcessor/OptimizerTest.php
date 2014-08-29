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
 * @subpackage GraphProcessor
 */

namespace ReckiCT\Analyzer\GraphProcessor;

use PHPUnit_Framework_TestCase as TestCase;

use Gliph\Graph\DirectedAdjacencyList;

use ReckiCT\Type;

use ReckiCT\Analyzer\GraphState;

use ReckiCT\Analyzer\OptimizerRule;

use ReckiCT\Graph\Vertex\Function_;

/**
 * @coversDefaultClass \ReckiCT\Analyzer\GraphProcessor\Optimizer
 */
class OptimizerTest extends TestCase
{
    /**
     * @covers ::process
     * @covers ::addRule
     */
    public function testOperation()
    {
        $graph = new DirectedAdjacencyList();
        $func = new Function_([], new Type(0), $graph);
        $graph->addVertex($func);
        $state = new GraphState($func);

        $resolver = new Optimizer();
        $resolver->addRule($r = $this->getMock(OptimizerRule::class));
        $r->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($func))
            ->will($this->returnValue(false));

        $resolver->process($state);

    }

}
