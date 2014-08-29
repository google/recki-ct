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
 * @package Util
 */
namespace ReckiCT\Util;

use Gliph\Graph\DirectedAdjacencyList;

use phpDocumentor\GraphViz\Node;
use phpDocumentor\GraphViz\Edge;
use phpDocumentor\GraphViz\Graph;

/**
 * Pretty print a graph into one of two formats
 *
 * This class requires that the GraphViz program be installed on the machine
 */
class GraphPrinter
{

    /**
     * Generate a textual representation of the graph
     *
     * @param \Gliph\Graph\DirectedAdjacencyList $graph The grpah to print
     *
     * @return string The generated graph
     */
    public function generateText(DirectedAdjacencyList $graph)
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'gvz');
        $this->convertGraph($graph)->export('canon', $tmpfile);
        $data = file_get_contents($tmpfile);
        unlink($tmpfile);

        return $data;
    }

    /**
     * Generate a image representation of the graph, and save to a faile
     *
     * @param \Gliph\Graph\DirectedAdjacencyList $graph    The grpah to print
     * @param string                             $filename The filename to save the graph to
     * @param string                             $format   The GraphViz format to render to
     *
     * @return string The generated graph
     * @codeCoverageIgnore
     */
    public function generateImage(DirectedAdjacencyList $graph, $filename, $format = 'svg')
    {
        $this->convertGraph($graph)->export($format, $filename);
    }

    /**
     * Convert a Graph to a phpDocumentor graph, usable for printing
     *
     * @param \Gliph\Graph\DirectedAdjacencyList $graph The graph to print
     *
     * @return \phpDocumentor\GraphViz\Graph The copied graph
     */
    protected function convertGraph(DirectedAdjacencyList $graph)
    {
        $new = Graph::create("dump");
        $nodes = new \SplObjectStorage();
        $ctr = 0;
        foreach ($graph->eachVertex() as $vertex => $_) {
            $nodes[$vertex] = Node::create('node_' . $ctr++, (string) $vertex);
            $new->setNode($nodes[$vertex]);
        }
        foreach ($graph->eachEdge() as $edge) {
            $new->link(Edge::create($nodes[$edge[0]], $nodes[$edge[1]]));
        }

        return $new;
    }

}
