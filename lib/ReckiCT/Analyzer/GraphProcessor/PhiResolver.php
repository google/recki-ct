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
use ReckiCT\Graph\Assignment;
use ReckiCT\Graph\Vertex\Phi;

use Gliph\Graph\DirectedAdjacencyList;

use ReckiCT\Analyzer\GraphState;

use ReckiCT\Analyzer\GraphProcessor;

class PhiResolver implements GraphProcessor
{
    public function process(GraphState $state)
    {
        $graph = $state->getGraph();
        do {
            $rerun = false;
            $phiNodes = [];
            foreach ($graph->vertices() as $vertex) {
                if ($vertex instanceof Phi) {
                    $phiNodes[] = $vertex;
                    $rerun = $rerun || $this->resolve($vertex, $state);
                }
            }
            if (!$rerun) {
                $rerun = $this->checkForCircularReference($phiNodes, $state);
            }
        } while ($rerun);
    }

    public function resolve(Phi $vertex, GraphState $state)
    {
        $types = [];
        foreach ($vertex->getVariables() as $value) {
            $types[] = $value->getType()->getType();
        }
        $types = array_unique($types);
        switch (count($types)) {
            case 1:
                if ($types[0] !== Type::TYPE_UNKNOWN || 0 === count($vertex->getValues())) {
                    // resolve variables to result
                    $this->removePhi($vertex, $state);

                    return true;
                }
                break;
            case 2:
                if (in_array(Type::TYPE_NUMERIC, $types)) {
                    if (in_array(Type::TYPE_LONG, $types) || in_array(Type::TYPE_DOUBLE, $types)) {
                        $this->removePhi($vertex, $state);

                        return true;
                    } elseif (in_array(Type::TYPE_BOOLEAN, $types)) {
                        $vertex->getResult()->setType(new Type(Type::TYPE_NUMERIC));
                        $this->removePhi($vertex, $state);

                        return true;
                    }
                }
                break;
        }

        return false;
    }

    public function removePhi(Phi $vertex, GraphState $state)
    {
        $graph = $state->getGraph();
        Helper::remove($vertex, $graph);
        $to = $vertex->getResult();
        foreach ($vertex->getValues() as $value) {
            foreach ($graph->vertices() as $v) {
                $v->replaceVariable($value, $to);
                if ($v instanceof Assignment && $v->getResult() === $value) {
                    $v->setResult($to);
                }
            }
        }
    }

    public function checkForCircularReference(array $phiNodes, GraphState $state)
    {
        $graph = new DirectedAdjacencyList();
        foreach ($phiNodes as $phi) {
            $result = $phi->getResult();
            foreach ($phi->getValues() as $value) {
                $graph->ensureArc($value, $result);
            }
        }
        $cycles = $graph->getCycles();
        foreach ($cycles as $cycle) {
            if ($this->areAllUnknown($cycle)) {
                // All are unknown, process!
                $types = array();
                foreach ($cycle as $component) {
                    foreach (Helper::getInboundNodes($component, $graph) as $node) {
                        if (in_array($node, $cycle, true)) continue;
                        $types[] = $node->getType()->getType();
                    }
                }
                $types = array_unique($types);
                $newType = 0;
                switch (count($types)) {
                    case 1:
                        if ($types[0] != Type::TYPE_UNKNOWN) {
                            $newType = $types[0];
                        }
                        break;
                    case 2:
                        if (in_array(Type::TYPE_NUMERIC, $types) && in_array(Type::TYPE_LONG, $types)) {
                            $newType = Type::TYPE_NUMERIC;
                        } elseif (in_array(Type::TYPE_NUMERIC, $types) && in_array(Type::TYPE_DOUBLE, $types)) {
                            $newType = Type::TYPE_DOUBLE;
                        }
                        break;
                }
                if ($newType) {
                    foreach ($cycle as $component) {
                        $component->setType(new Type($newType));
                    }

                    return true;
                }
            }
        }

        return false;
    }

    public function areAllUnknown(array $phiNodes)
    {
        foreach ($phiNodes as $node) {
            if (!$node->getType()->isUnknown()) {
                return false;
            }
        }

        return true;
    }
}
