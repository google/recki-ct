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

use PhpParser\Node\Expr\BinaryOp\Pow;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use PhpParser\Node\Expr\BinaryOp\ShiftLeft;
use PhpParser\Node\Expr\BinaryOp\ShiftRight;

/**
 * Resolve assignment operations to separate assignment and operations
 *
 * So `$a += $b` will become `$a = $a + $b`
 */
class AssignOpResolver extends NodeVisitorAbstract
{
    protected $mapping = array(
        'Expr_AssignOp_BitwiseAnd'  => BitwiseAnd::class,
        'Expr_AssignOp_BitwiseOr'   => BitwiseOr::class,
        'Expr_AssignOp_BitwiseXor'  => BitwiseXor::class,
        'Expr_AssignOp_Concat'      => Concat::class,
        'Expr_AssignOp_Div'         => Div::class,
        'Expr_AssignOp_Minus'       => Minus::class,
        'Expr_AssignOp_Mod'         => Mod::class,
        'Expr_AssignOp_Mul'         => Mul::class,
        'Expr_AssignOp_Plus'        => Plus::class,
        'Expr_AssignOp_Pow'         => Pow::class,
        'Expr_AssignOp_ShiftLeft'   => ShiftLeft::class,
        'Expr_AssignOp_ShiftRight'  => ShiftRight::class,
    );

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node)
    {
        if (isset($this->mapping[$node->getType()])) {
            // We have a mapping, so resolve the assign operation to
            // Separate assign and operation nodes
            $class = $this->mapping[$node->getType()];

            return new Node\Expr\Assign(
                $node->var,
                new $class($node->var, $node->expr)
            );
        }
    }

}
