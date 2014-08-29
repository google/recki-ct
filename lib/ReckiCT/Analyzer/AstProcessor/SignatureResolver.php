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
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Expr\FuncCall;

use ReckiCT\Analyzer\SignatureResolver as Worker;

class SignatureResolver extends NodeVisitorAbstract
{
    protected $worker;

    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }

    /**
     * Called when entering a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node Node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Function_) {
            $signature = $this->worker->resolve($node);

            $node->jitType = $signature->getReturn();

            foreach ($node->params as $key => $param) {
                $type = $signature->getParam($key);
                if ($param->type && !$type->equals($param->type)) {
                    throw new \LogicException("Param type mismatch, expecting $type, got {$param->type}");
                }
                $param->jitType = $type;
            }
        } elseif ($node instanceof FuncCall) {
            $signature = $this->worker->resolve($node->name->toString());
            $node->signature = $signature;
        }
    }

}
