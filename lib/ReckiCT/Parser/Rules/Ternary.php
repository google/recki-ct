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
use PhpParser\Node\Expr\Ternary as AstTernary;
use ReckiCT\Graph\Vertex\Assign as JitAssign;
use ReckiCT\Graph\Vertex\Jump as JitJump;
use ReckiCT\Graph\Vertex\JumpZ as JitJumpZ;
use ReckiCT\Graph\Vertex\NoOp as JitNoOp;
use ReckiCT\Graph\Variable;

class Ternary implements Rule
{
    public function test(Node $node)
    {
        return $node instanceof AstTernary;
    }

    public function parse(Node $stmt, State $state)
    {
        $cond = $state->parser->parseNode($stmt->cond, $state);
        $result = new Variable();
        $label = new JitNoOp();
        $end = new JitNoOp();
        $jmp = $state->addVertex(new JitJumpZ($label, $cond));
        $state->graph->addDirectedEdge($jmp, $label);
        if ($stmt->if) {
            $state->addVertex(new JitAssign(
                $state->parser->parseNode($stmt->if, $state),
                $result
            ));
        } else {
            $state->addVertex(new JitAssign(
                $cond,
                $result
            ));
        }
        $state->addVertex(new JitJump());
        $state->addVertex($end);
        $state->last = $label;
        $state->addVertex(new JitAssign(
            $state->parser->parseNode($stmt->else, $state),
            $result
        ));
        $state->addVertex($end);

        return $result;
    }

}
