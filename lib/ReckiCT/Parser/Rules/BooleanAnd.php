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
use PhpParser\Node\Expr\BinaryOp\BooleanAnd as AstBooleanAnd;

use ReckiCT\Type;
use ReckiCT\Graph\Constant;
use ReckiCT\Graph\Variable;
use ReckiCT\Graph\Vertex;

class BooleanAnd implements Rule
{
    public function test(Node $node)
    {
        return $node instanceof AstBooleanAnd;
    }

    public function parse(Node $stmt, State $state)
    {
        $result = new Variable(new Type(Type::TYPE_BOOLEAN));
        $false = new Constant(false);
        $state->addVertex(new Vertex\Assign(
            $false,
            $result
        ));
        $end = new Vertex\NoOp();
        $left = $state->parser->parseNode($stmt->left, $state);
        $jmp = $state->addVertex(new Vertex\JumpZ(
            $end,
            $left
        ));
        $state->graph->addDirectedEdge($jmp, $end);
        $right = $state->parser->parseNode($stmt->right, $state);
        $state->addVertex(new Vertex\Assign(
            $right,
            $result
        ));
        $state->addVertex(new Vertex\Jump());
        $state->addVertex($end);

        return $result;
    }

}
