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
 * @subpackage Algo
 */

namespace ReckiCT\Graph\Algo;

use ReckiCT\Util\Shim;

use ReckiCT\Graph\Helper;
use ReckiCT\Graph\Vertex;
use Gliph\Graph\Digraph;

/**
 * This class accepts a Graph and a Vertex, and builds a dominator tree
 * based off of that Vertex.
 */
class Dominator
{
    /**
     * @var Digraph The graph node for the tree
     */
    protected $graph;

    /**
     * @var \SplObjectStorage The dominator tree
     */
    protected $dominator;

    /**
     * @var \SplObjectStorage A cache of all predecessors of each Vertex
     */
    protected $predecessors;

    /**
     * @var \SplObjectStorage A cache of all immediate predecessors of each Vertex
     */
    protected $iPredecessors;

    /**
     * Build the dominator tree, and initialize it
     *
     * @param DirectedAdjacencyList $graph The graph to build the tree for
     * @param Vertex                $start The start node of the dominator
     */
    public function __construct(Digraph $graph, Vertex $start)
    {
        $this->dominator = new \SplObjectStorage();
        $this->graph = $graph;
        $this->predecessors = Helper::computePredecessors($this->graph);
        $this->iPredecessors = Helper::computeImmediatePredecessors($this->graph);
        $this->build($start);
    }

    /**
     * Determines if a vertex dominates another
     *
     * @param Vertex $vertex    The vertex
     * @param Vertex $dominator The candidate dominator of the given vertex
     *
     * @return boolean If the dominator does indeed dominate the vertex
     */
    public function dominates(Vertex $vertex, Vertex $dominator)
    {
        if (!$this->dominator->contains($vertex)) {
            // workaround for insertion of phi nodes
            return false;
        }
        foreach ($this->dominator[$vertex] as $node) {
            if ($node === $dominator) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if a vertex strictly dominates another vertex.
     * The difference that strictly dominates enforces over dominates is that
     * a node always dominates itself, but does not strictly dominate itself.
     *
     * @param Vertex $vertex    The vertex
     * @param Vertex $dominator The candidate dominator of the given vertex
     *
     * @return boolean If the dominator does indeed strictly dominate the vertex
     */
    public function strictlyDominates(Vertex $vertex, Vertex $dominator)
    {
        return $vertex !== $dominator && $this->dominates($vertex, $dominator);
    }

    /**
     * Find the immediate dominator of a vertex (idom)
     *
     * The idom is the unique dominator of the vertex which dominates
     * no other dominator of the vertex.
     *
     * @param Vertex $vertex The vertex
     *
     * @return mixed The vertex or null if no idom exists
     */
    public function immediateDominator(Vertex $vertex)
    {
        $candidates = $this->dominator[$vertex];
        foreach ($candidates as $c1) {
            if ($c1 === $vertex) {
                // A vertex cannot be its own immediate dominator
                continue;
            }
            foreach ($candidates as $c2) {
                if ($this->strictlyDominates($c2, $c1) && $this->strictlyDominates($vertex, $c2)) {
                    // The candidate strictly dominates another candidate, reject
                    continue 2;
                }
            }

            return $c1;
        }
    }

    /**
     * Find the immediate dominator of a set of vertices
     *
     * The idom is the unique dominator of the vertex which dominates
     * no other dominator of the vertex.
     *
     * @param Vertex[] $vertices The vertex
     *
     * @return mixed The vertex or null if no idom exists
     */
    public function immediateDominatorArray(array $vertices)
    {
        $idoms = [];
        foreach ($vertices as $vertex) {
            $idoms[] = $this->immediateDominator($vertex);
        }
        while (!empty($idoms)) {
            $idoms = array_filter(Shim::array_unique($idoms));
            $toremove = [];
            foreach ($idoms as $k => $v1) {
                foreach ($idoms as $v2) {
                    if ($this->strictlyDominates($v1, $v2)) {
                        $toremove[] = $k;
                    }
                }
            }
            foreach ($toremove as $k) {
                unset($idoms[$k]);
            }
            if (count($idoms) === 1) {
                return reset($idoms);
            }
            $newidoms = [];
            foreach ($idoms as $idom) {
                $newidoms[] = $this->immediateDominator($idom);
            }
            $idoms = $newidoms;
        }
    }

    /**
     * Get the dominance frontier of a vertex
     *
     * The frontier is the set of vertices where the Vertex $a dominates
     * the predecessor of the candidate vertex, but not the candidate
     * vertex itself
     *
     * @param Vertex $a The vertex to get the frontier for
     *
     * @return Vertex[] All frontier vertices for the given vertex
     */
    public function getFrontier(Vertex $a)
    {
        $result = array();
        foreach ($this->graph->vertices() as $vertex) {
            if ($this->strictlyDominates($vertex, $a)) {
                continue;
            }
            if (!$this->iPredecessors->contains($vertex)) {
                // phi node
                continue;
            }
            foreach ($this->iPredecessors[$vertex] as $pred) {
                if ($this->dominates($pred, $a)) {
                    $result[] = $vertex;
                    continue 2;
                }
            }
        }

        return $result;
    }

    /**
     * Build the dominator tree
     *
     * @param Vertex $start The start vertex for the tree
     */
    protected function build(Vertex $start)
    {
        $this->dominator[$start] = [$start]; // The dominator of the function is itself.
        foreach ($this->graph->vertices() as $vertex) {
            if ($vertex === $start) {
                continue;
            }
            $dominated = array();
            foreach ($this->graph->vertices() as $sub) {
                if ($sub === $start) {
                    continue;
                }
                // Start by adding all vertexes to the dominator Graph
                $dominated[] = $sub;
            }
            $this->dominator[$vertex] = $dominated;
        }

        $this->reduce($start);
    }

    /**
     * Reduce the generated tree to only contain the actual dominators
     *
     * This is an inefficient algorithm. It is approximately O(n^2).
     */
    protected function reduce(Vertex $start)
    {
        $changed = true;

        while ($changed) {
            $changed = false;
            foreach ($this->graph->vertices() as $vertex) {
                if ($vertex === $start) continue;
                $pred = $this->iPredecessors[$vertex];

                if (empty($pred)) continue;

                $new = $this->dominator[$pred[0]];

                for ($i = 1; $i < count($pred); $i++) {
                    $new = Shim::array_intersect($new, $this->dominator[$pred[$i]]);
                }

                $new = array_merge($new, [$vertex]);

                if ($new !== $this->dominator[$vertex]) {
                    $changed = true;
                    $this->dominator[$vertex] = $new;
                }
            }
        }
    }

}
