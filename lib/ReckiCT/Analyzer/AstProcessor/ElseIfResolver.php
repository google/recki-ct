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
 * @subpackage AstProcessor
 */

namespace ReckiCT\Analyzer\AstProcessor;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Else_;

class ElseIfResolver extends NodeVisitorAbstract
{
    /**
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * false:     $node is removed from the parent array
     *  * array:     The return value is merged into the parent array (at the position of the $node)
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node|false|Node[] Node
     */
    public function leaveNode(Node $node)
    {
        if ($node->getType() == "Stmt_If") {
            return $this->expandElseIfs($node, $node->else);
        }
    }

    protected function expandElseIfs(Node $node)
    {
        if (!$node->elseifs) {
            return $node;
        }
        $first = array_shift($node->elseifs);

        $newIf = new If_(
            $first->cond,
            [
                'stmts' => $first->stmts,
                'elseifs' => $node->elseifs,
                'else' => $node->else
            ]
        );

        $node->else = new Else_(
            [
                $this->expandElseIfs($newIf)
            ]
        );

        $node->elseifs = array();

        return $node;
    }

}
