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

use ReckiCT\Graph\Class_;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Variable as JitVariable;
use ReckiCT\Graph\Constant as JitConstant;
use ReckiCT\Graph\Vertex\End as JitEnd;
use ReckiCT\Graph\Vertex\Return_ as JitReturn;
use ReckiCT\Graph\Vertex\Function_ as JitFunction;

use PhpParser\Node;
use PhpParser\Node\Scalar;
use PhpParser\Node\Expr\Variable as AstVariable;
use PhpParser\Node\Stmt\Class_ as AstClass;
use PhpParser\Node\Stmt\ClassMethod as AstClassMethod;
use PhpParser\Node\Stmt\Function_ as AstFunction;
use Gliph\Graph\DirectedAdjacencyList;

class Parser
{
    protected $rules = [];

    public function addRule(Rule $rule)
    {
        $this->rules[] = $rule;
    }

    public function parseClass(AstClass $ast)
    {
        var_dump($ast);
        $implements = [];
        foreach ($ast->implements as $interface) {
            $implements[] = $interface->toString();
        }
        $class = new Class_(
            $ast->namespacedName->toString(),
            $ast->extends ? $ast->extends->toString() : null,
            $implements
        );

        foreach ($ast->stmts as $stmt) {
            switch (get_class($stmt)) {
                case AstClassMethod::class:
                    break;
                default:
                    throw new \LogicException("Unexpected child type found: " . get_class($stmt));
            }
        }
        return $class;
    }

    public function parseFunction(AstFunction $ast)
    {
        $args = array();
        foreach ($ast->params as $param) {
            $args[$param->name] = new JitVariable($param->jitType);
        }

        $func = new JitFunction($args, $ast->jitType, new DirectedAdjacencyList());

        $state = new State($this, $func->getGraph());
        $state->scope = $args;
        $state->last = $func;

        $this->parseStmtList($ast->stmts, $state);

        $this->addEndNode($state);

        return $func;
    }

    public function parseStmtList(array $stmts, State $state)
    {
        foreach ($stmts as $stmt) {
            $this->parseNode($stmt, $state);
        }
    }

    public function parseNode(Node $stmt, State $state)
    {
        if ($stmt instanceof AstVariable) {
            return $state->findVariable($stmt);
        } elseif ($stmt instanceof Scalar) {
            return new JitConstant($stmt->value);
        }
        foreach ($this->rules as $rule) {
            if ($rule->test($stmt)) {
                return $rule->parse($stmt, $state);
            }
        }
        throw new \LogicException('Found node without parser rule: ' . $stmt->getType());
    }

    public function addEndNode(State $state)
    {
        $end = $state->addVertex(new JitEnd());
        foreach ($state->graph->vertices() as $vertex) {
            if ($vertex instanceof JitReturn) {
                $state->graph->ensureArc($vertex, $end);
            }
        }
    }

}
