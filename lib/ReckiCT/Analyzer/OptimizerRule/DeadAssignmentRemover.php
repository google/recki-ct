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
 * @subpackage OptimizerRule
 */

namespace ReckiCT\Analyzer\OptimizerRule;

use Gliph\Graph\Digraph;

use ReckiCT\Graph\Assignment;
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Helper;

use ReckiCT\Analyzer\OptimizerRule;

class DeadAssignmentRemover implements OptimizerRule
{
    public function process(Vertex $vertex, Digraph $graph)
    {
        if ($vertex instanceof Assignment && $vertex->isIdempotent() && 1 >= $this->countUsages($vertex->getResult(), $graph)) {
            Helper::remove($vertex, $graph);

            return true;
        }

        return false;
    }

    public function countUsages(Variable $var, Digraph $graph)
    {
        $counter = 0;
        foreach ($graph->vertices() as $vtx) {
            if (in_array($var, $vtx->getVariables(), true)) {
                $counter++;
            }
        }

        return $counter;
    }

}
