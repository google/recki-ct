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

use ReckiCT\Graph\Variable;

use PhpParser\Node;

use ReckiCT\Graph\Vertex\BinaryOp as JitOp;

use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use PhpParser\Node\Expr\BinaryOp\ShiftLeft;
use PhpParser\Node\Expr\BinaryOp\ShiftRight;

class BinaryOp implements Rule
{
    protected static $map = [
        Concat::class => JitOp::CONCAT,

        Plus::class     => JitOp::PLUS,
        Minus::class    => JitOp::MINUS,
        Mul::class      => JitOp::MUL,
        Div::class      => JitOp::DIV,
        Mod::class      => JitOp::MOD,

        Equal::class        => JitOp::EQUAL,
        NotEqual::class     => JitOp::NOT_EQUAL,
        Identical::class    => JitOp::IDENTICAL,
        NotIdentical::class => JitOp::NOT_IDENTICAL,

        Greater::class          => JitOp::GREATER,
        GreaterOrEqual::class   => JitOp::GREATER_EQUAL,
        Smaller::class          => JitOp::SMALLER,
        SmallerOrEqual::class   => JitOp::SMALLER_EQUAL,

        BitwiseAnd::class   => JitOp::BITWISE_AND,
        BitwiseOr::class    => JitOp::BITWISE_OR,
        BitwiseXor::class   => JitOp::BITWISE_XOR,
        ShiftRight::class   => JitOp::SHIFT_RIGHT,
        ShiftLeft::class    => JitOp::SHIFT_LEFT,
    ];

    public function test(Node $node)
    {
        return isset(self::$map[get_class($node)]);
    }

    public function parse(Node $stmt, State $state)
    {
        $kind = self::$map[get_class($stmt)];
        $a = $state->parser->parseNode($stmt->left, $state);
        $b = $state->parser->parseNode($stmt->right, $state);
        $state->addVertex(new JitOp(
            $kind,
            $a,
            $b,
            $result = new Variable()
        ));

        return $result;
    }

}
