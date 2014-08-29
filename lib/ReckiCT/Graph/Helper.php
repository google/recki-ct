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
 * @package Graph
 */

namespace ReckiCT\Graph;

use ReckiCT\Graph\Vertex\Phi as JitPhi;

use ReckiCT\Util\Shim;

use Gliph\Graph\DirectedAdjacencyList;

class Helper
{
    public static function insertAfter(Vertex $old, Vertex $new, DirectedAdjacencyList $graph)
    {
        $toRemove = array();
        foreach ($graph->eachAdjacent($old) as $node) {
            $graph->addDirectedEdge($new, $node);
            $toRemove[] = $node;
        }
        $graph->addDirectedEdge($old, $new);
        foreach ($toRemove as $node) {
            $graph->removeEdge($old, $node);
        }
    }

    public static function replace(Vertex $old, Vertex $new, DirectedAdjacencyList $graph)
    {
        foreach (self::getInboundNodes($old, $graph) as $n1) {
            $graph->addDirectedEdge($n1, $new);
        }
        foreach ($graph->eachAdjacent($old) as $n2) {
            $graph->addDirectedEdge($new, $n2);
        }
        $graph->removeVertex($old);
    }

    public static function remove(Vertex $old, DirectedAdjacencyList $graph)
    {
        foreach (self::getInboundNodes($old, $graph) as $n1) {
            foreach ($graph->eachAdjacent($old) as $n2) {
                $graph->addDirectedEdge($n1, $n2);
            }
        }
        $graph->removeVertex($old);
    }

    public static function getInboundNodes($vertex, DirectedAdjacencyList $graph)
    {
        // run each time, since the graph may change...
        return $graph->transpose()
            ->eachAdjacent($vertex);
    }

    public static function computePredecessors(DirectedAdjacencyList $graph)
    {
        $reversed = $graph->transpose();
        $predecessors = new \SplObjectStorage();
        foreach ($reversed->eachVertex() as $vertex => $_) {
            $predecessors[$vertex] = self::findAllSuccessors($vertex, $reversed);
        }

        return $predecessors;
    }

    public static function computeImmediatePredecessors(DirectedAdjacencyList $graph)
    {
        $reversed = $graph->transpose();
        $predecessors = new \SplObjectStorage();
        foreach ($reversed->eachVertex() as $vertex => $_) {
            $tmp = array();
            foreach ($reversed->eachAdjacent($vertex) as $sub) {
                $tmp[] = $sub;
            }
            $predecessors[$vertex] = Shim::array_unique($tmp);
        }

        return $predecessors;
    }

    protected static $processing;

    public static function findAllSuccessors(Vertex $node, DirectedAdjacencyList $graph)
    {
        if (is_null(self::$processing)) {
            self::$processing = new \SplObjectStorage();
        }
        if (self::$processing->contains($node)) {
            return array();
        }
        self::$processing[$node] = true;
        $result = array();
        foreach ($graph->eachAdjacent($node) as $sub) {
            $result[] = $sub;
            $result = array_merge($result, self::findAllSuccessors($sub, $graph));
        }
        self::$processing->detach($node);

        return Shim::array_unique($result);
    }

    public static function findVerticesByClass($class, DirectedAdjacencyList $graph)
    {
        $result = [];
        foreach ($graph->eachVertex() as $vertex => $_) {
            if ($vertex instanceof $class) {
                $result[] = $vertex;
            }
        }

        return $result;
    }

    public static function findVariables(DirectedAdjacencyList $graph)
    {
        $vars = new \SplObjectStorage();
        foreach ($graph->eachVertex() as $vertex => $_) {
            foreach ($vertex->getVariables() as $var) {
                if (!$var instanceof Constant) {
                    $vars->attach($var);
                }
            }
        }

        return iterator_to_array($vars);
    }

    public static function isPhiVar(Variable $var, DirectedAdjacencyList $graph)
    {
        foreach ($graph->eachVertex() as $vertex => $_) {
            if ($vertex instanceof JitPhi && in_array($var, $vertex->getVariables(), true)) {
                return true;
            }
        }

        return false;
    }

    public static function isLiveVar(Variable $var, Vertex $vtx, DirectedAdjacencyList $graph)
    {
        static $seen = [];
        if (in_array($vtx, $seen, true)) {
            return false;
        } elseif (in_array($var, $vtx->getVariables(), true)) {
            return true;
        }

        $seen[] = $vtx;
        foreach ($graph->eachAdjacent($vtx) as $sub) {
            if (self::isLiveVar($var, $sub, $graph)) {
                array_pop($seen);

                return true;
            }
        }
        array_pop($seen);

        return false;
    }

}
