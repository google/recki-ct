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
use PhpParser\Node\Expr\PostInc as AstPostInc;
use PhpParser\Node\Expr\PostDec as AstPostDec;

use ReckiCT\Graph\Vertex\Assign as JitAssign;
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;
use ReckiCT\Graph\Constant;
use ReckiCT\Graph\Variable;

class PostOp implements Rule
{
    public function test(Node $node)
    {
        return $node instanceof AstPostDec || $node instanceof AstPostInc;
    }

    public function parse(Node $stmt, State $state)
    {
        $var = $state->parser->parseNode($stmt->var, $state);
        $result = new Variable();
        $state->addVertex(new JitAssign(
            $var,
            $result
        ));
        $state->addVertex(new JitBinaryOp(
            $stmt instanceof AstPostInc ? JitBinaryOp::PLUS : JitBinaryOp::MINUS,
            $var,
            new Constant(1),
            $var
        ));

        return $result;
    }

}
