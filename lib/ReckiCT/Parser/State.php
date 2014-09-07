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
 * @package Parser
 */

namespace ReckiCT\Parser;

use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Variable;

use Gliph\Graph\Digraph;

use PhpParser\Node;

/**
 * A helper object for parsing.
 *
 * This represents the current state of the parser.
 *
 * @internal
 */
class State
{

    /**
     * @var \ReckiCT\Parser\Parser The parser instance
     */
    public $parser;

    /**
     * @var \Gliph\Graph\Digraph The graph being generated
     */
    public $graph;

    /**
     * @var array An associative array of variable name => ReckiCT\Graph\Variable
     */
    public $scope = array();

    /**
     * @var \ReckiCT\Graph\Vertex The last vertex encountered
     */
    public $last;

    /**
     * This variable contains issued labels, for reference by future jumps
     *
     * @var array An associative array of label name => ReckiCT\Graph\Vertex\NoOP
     */
    public $labels = array();

    /**
     * This variable contains jumps to future labels
     *
     * @var array An associative array of label name => [ReckiCT\Graph\Vertex]
     */
    public $gotolist = array();

    /**
     * Construct a new state instance
     *
     * @param \ReckiCT\Parser\Parser             $parser The parser instance for this run
     * @param \Gliph\Graph\DirectedAdjacencyList $graph  The graph being built
     */
    public function __construct(Parser $parser, Digraph $graph)
    {
        $this->parser = $parser;
        $this->graph = $graph;
    }

    /**
     * Find a variable, creating it if it does not yet exist
     *
     * @param string|\PhpParser\Node\Expr\Variable $name The variable to locate
     *
     * @return \ReckiCT\Graph\Variable The variable, created if necessary
     *
     * @throws \InvalidArgumentException If the $name is not valid (not a string or Expr)
     */
    public function findVariable($name)
    {
        $type = null;
        if ($name instanceof Node\Expr\Variable) {
            $type = $name->jitType;
            $name = $name->name;
        }
        if (!is_string($name)) {
            throw new \InvalidArgumentException("Expecting a string variable name, or an instance of Node\Expr\Variable");
        }
        if (!isset($this->scope[$name])) {
            $this->scope[$name] = new Variable($type);
        }

        return $this->scope[$name];
    }

    /**
     * Add a vertex to the graph.
     *
     * This also updates the `$last` state property, so the next added node will
     * become a child of this one.
     *
     * @param \ReckiCT\Graph\Vertex $new The new vertex to add to the graph
     *
     * @return \ReckiCT\Graph\Vertex The added vertex
     */
    public function addVertex(Vertex $new)
    {
        if ($this->last) {
            $this->graph->ensureArc($this->last, $new);
        }
        $this->last = $new;

        return $new;
    }

}
