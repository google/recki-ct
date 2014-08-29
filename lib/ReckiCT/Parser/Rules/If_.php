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
 * @subpackage Rules
 */

namespace ReckiCT\Parser\Rules;

use ReckiCT\Parser\Rule;
use ReckiCT\Parser\State;

use PhpParser\Node;
use PhpParser\Node\Stmt\If_ as AstIf;
use ReckiCT\Graph\Vertex\Jump;
use ReckiCT\Graph\Vertex\NoOp;
use ReckiCT\Graph\Vertex\JumpZ;

class If_ implements Rule
{
    public function test(Node $node)
    {
        return $node instanceof AstIf;
    }

    public function parse(Node $stmt, State $state)
    {
        $end = new NoOp();
        $cond = $state->parser->parseNode($stmt->cond, $state);
        if ($stmt->else) {
            $next = new NoOp();
            $if = $state->addVertex(new JumpZ($next, $cond));
            $state->graph->addDirectedEdge($if, $next);
            $state->parser->parseStmtList($stmt->stmts, $state);
            $jmp = $state->addVertex(new Jump());
            $state->graph->addDirectedEdge($jmp, $end);
            $state->last = null;

            $state->addVertex($next);
            $state->parser->parseStmtList($stmt->else->stmts, $state);
            $state->addVertex(new Jump());
        } else {
            $if = $state->addVertex(new JumpZ($end, $cond));
            $state->parser->parseStmtList($stmt->stmts, $state);
            $state->addVertex(new Jump());
            $state->addVertex($end);
            $state->last = $if;
        }
        $state->addVertex($end);
    }

}
