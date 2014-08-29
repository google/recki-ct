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
use PhpParser\Node\Expr\FuncCall as AstFunctionCall;

use ReckiCT\Graph\Variable as JitVariable;
use ReckiCT\Graph\Vertex\FunctionCall as JitFunctionCall;

class FunctionCall implements Rule
{
    public function test(Node $node)
    {
        return $node instanceof AstFunctionCall;
    }

    public function parse(Node $stmt, State $state)
    {
        if (!$stmt->name instanceof Node\Name) {
            throw new \LogicException("Variable function calls are not supported");
        }

        $args = array();
        foreach ($stmt->args as $arg) {
            $args[] = $state->parser->parseNode($arg->value, $state);
        }

        $retvar = new JitVariable($stmt->signature->getReturn());

        $call = $state->addVertex(new JitFunctionCall(
            $stmt->name->toString(),
            $args,
            $retvar,
            $stmt->signature,
            $stmt->isSelfRecursive
        ));

        return $retvar;
    }

}
