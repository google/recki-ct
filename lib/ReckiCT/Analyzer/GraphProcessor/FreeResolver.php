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
 * @package Analyzer
 * @subpackage GraphProcessor
 */

namespace ReckiCT\Analyzer\GraphProcessor;

use ReckiCT\Type;
use ReckiCT\Graph\Helper;
use ReckiCT\Graph\Vertex\Free;
use ReckiCT\Graph\Vertex\Jump;
use ReckiCT\Graph\Vertex\JumpZ;

use Gliph\Graph\DirectedAdjacencyList;

use ReckiCT\Analyzer\GraphState;

use ReckiCT\Analyzer\GraphProcessor;

class FreeResolver implements GraphProcessor
{
    public function process(GraphState $state)
    {
        $graph = $state->getGraph();
        $vars = Helper::findVariables($graph);
        $postdominator = $state->getPostDominator();
        $dominator = $state->getDominator();

        foreach ($vars as $var) {
            $usages = [];
            foreach ($graph->vertices() as $vertex) {
                if (in_array($var, $vertex->getVariables(), true)) {
                    $usages[] = $vertex;
                }
            }

            $dom = $postdominator->immediateDominatorArray($usages);
            while ($dom) {
                foreach ($usages as $usage) {
                    if ($dominator->strictlyDominates($usage, $dom)) {
                        $dom = $postdominator->immediateDominator($dom);

                        continue 2;
                    }
                }
                break;
            }

            if (!$dom) {
                continue;
            }

            if ($dom instanceof Jump || $dom instanceof JumpZ) {
                Helper::insertBefore($dom, new Free($var), $graph);
            } else {
                Helper::insertAfter($dom, new Free($var), $graph);
            }
        }
    }


}