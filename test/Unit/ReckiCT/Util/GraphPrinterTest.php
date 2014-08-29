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
 * @package Util
 */

namespace ReckiCT\Util;

use PHPUnit_Framework_TestCase as TestCase;

use Gliph\Graph\DirectedAdjacencyList;

/**
 * @coversDefaultClass \ReckiCT\Util\GraphPrinter
 */
class GraphPrinterTest extends TestCase
{
    public function setUp()
    {
        // check for `dot`
        $test = exec('dot -V 2>&1', $out, $ret);
        if ($ret != 0) {
            $this->markTestSkipped('Skipping tests when GraphViz is not installed');
        }
    }

    /**
     * @covers ::generateText
     * @covers ::convertGraph
     */
    public function testGenerateText()
    {
        $a = new DummyVertex('a');
        $b = new DummyVertex('b');
        $c = new DummyVertex('c');

        $graph = new DirectedAdjacencyList();
        $graph->addDirectedEdge($a, $b);
        $graph->addDirectedEdge($b, $c);
        $graph->addDirectedEdge($c, $b);

        $printer = new GraphPrinter();
        $this->assertContains('node_0', $printer->generateText($graph));
    }

}
