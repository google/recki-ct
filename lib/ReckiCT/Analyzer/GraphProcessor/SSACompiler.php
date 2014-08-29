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

use ReckiCT\Analyzer\GraphState;

use ReckiCT\Graph\Helper;
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Assignment as JitAssignment;
use ReckiCT\Graph\Vertex\End;
use ReckiCT\Graph\Vertex\Phi;
use ReckiCT\Graph\Vertex\NoOp as JitNoOp;
use ReckiCT\Graph\Algo\Dominator;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;
use ReckiCT\Analyzer\GraphProcessor;

use Gliph\Graph\DirectedAdjacencyList;

class SSACompiler implements GraphProcessor
{
    protected $stack;

    public function __construct()
    {
        $this->stack = new \SplObjectStorage();
    }

    public function process(GraphState $state)
    {
        $graph = $state->getGraph();
        $assignments = array_merge(
            Helper::findVerticesByClass(JitAssignment::class, $graph),
            [$state->getFunction()]
        );
        $vars = Helper::findVariables($graph);
        $dominator = $state->getDominator();
        foreach ($vars as $var) {
            $hash = spl_object_hash($var);

            $varAssignments = $this->findAssignmentsByVar($var, $assignments);

            $phi = $this->findPhiNodes($var, $varAssignments, $dominator, $graph);

            $this->implementSSA($var, $var, $state->getFunction(), $graph, $phi, $state->getFunction()->getArguments());

        }
    }

    public function implementSSA(Variable $old, Variable $new, Vertex $vertex, DirectedAdjacencyList $graph, \SplObjectStorage $phiNodes, array $args)
    {
        if ($this->stack->contains($vertex)) {
            if (isset($phiNodes[$vertex])) {
                // we've visited, so it **must** have a node implemented
                $phiNodes[$vertex]->addValue($new);
            }

            return;
        }
        $this->stack->attach($vertex);
        if ($old !== $new && !$vertex instanceof Phi) {
            $vertex->replaceVariable($old, $new);
        }

        if ($vertex instanceof JitAssignment && $vertex->getResult() === $old) {
            $new = new Variable($new->getType());
            $vertex->setResult($new);
        }

        if (isset($phiNodes[$vertex])) {
            if ($phiNodes[$vertex] === true) {
                // insert phiNode
                $next = new Variable();
                $phi = new Phi($next);
                $phiNodes[$vertex] = $phi;
                if ($old !== $new || in_array($old, $args, true)) {
                    $phiNodes[$vertex]->addValue($new);
                }
                Helper::insertAfter($vertex, $phiNodes[$vertex], $graph);
                $new = $next;
            } else {
                $phiNodes[$vertex]->addValue($new);
            }
        }

        foreach ($graph->eachAdjacent($vertex) as $sub) {
            // Depth first search
            $this->implementSSA($old, $new, $sub, $graph, $phiNodes, $args);
        }

        $this->stack->detach($vertex);
    }

    public function findAssignmentsByVar(Variable $var, array $assignments)
    {
        $return = array();
        foreach ($assignments as $assignment) {
            if ($assignment instanceof JitAssignment && $assignment->getResult() === $var) {
                $return[] = $assignment;
            } elseif ($assignment instanceof JitFunction && $assignment->hasArgument($var)) {
                $return[] = $assignment;
            }
        }

        return $return;
    }

    public function findPhiNodes(Variable $var, array $assignments, Dominator $dominator, DirectedAdjacencyList $graph)
    {
        $allDf = new \SplObjectStorage();
        $new = $assignments;

        do {
            $runAgain = false;
            $new = $this->findFrontier($new, $dominator);
            foreach ($new as $obj) {
                if (!$allDf->contains($obj)) {
                    $allDf->attach($obj);
                    // found a new one!!!
                    $runAgain = true;
                }
            }
        } while ($runAgain);

        $phiNodes = new \SplObjectStorage();
        foreach ($allDf as $node) {
            if ($node instanceof JitNoOp) {
                if (Helper::isLiveVar($var, $node, $graph)) {
                    // only add the phi node if the variable is live afterwards
                    $phiNodes[$node] = true;
                }
            } elseif (! $node instanceof End) {
                throw new \RuntimeException('A non-NoOp Phi Node was found, possible corrupted graph');
            }
        }

        return $phiNodes;
    }

    public function findFrontier(array $nodes, Dominator $dominator)
    {
        $result = array();
        foreach ($nodes as $node) {
            $result = array_merge($result, $dominator->getFrontier($node));
        }

        return $result;
    }

}
