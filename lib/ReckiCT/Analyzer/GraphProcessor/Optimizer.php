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

use ReckiCT\Analyzer\OptimizerRule;

use ReckiCT\Analyzer\GraphState;

use ReckiCT\Analyzer\GraphProcessor;

class Optimizer implements GraphProcessor
{
    protected $rules = [];

    /**
     * Actually run the optimization rules over the graph.
     *
     * This will re-run the optimizers if anything was changed in a run. This
     * allows for optimizations to happen in any order, and for one optimization
     * to open the door to another.
     *
     * Unfortunately, this also makes this process EXTREMELY expensive.
     *
     * This is O(scary).
     */
    public function process(GraphState $state)
    {
        $graph = $state->getGraph();
        do {
            $rerun = false;

            foreach ($graph->vertices() as $vertex) {
                foreach ($this->rules as $rule) {
                    $rerun = $rerun || $rule->process($vertex, $graph);
                }
            }
        } while ($rerun);
    }

    public function addRule(OptimizerRule $rule)
    {
        $this->rules[] = $rule;
    }

}
