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

use ReckiCT\Graph\Constant;
use ReckiCT\Graph\Variable;

use PhpParser\Node;
use PhpParser\Node\Expr\BitwiseNot as AstBitwiseNot;
use ReckiCT\Graph\Vertex\BitwiseNot as JitBitwiseNot;

use PhpParser\Node\Expr\BooleanNot as AstBooleanNot;
use ReckiCT\Graph\Vertex\BooleanNot as JitBooleanNot;

use PhpParser\Node\Expr\UnaryMinus as AstUnaryMinus;
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;

class UnaryOp implements Rule
{
    protected static $map = [
        AstBitwiseNot::class => JitBitwiseNot::class,
        AstBooleanNot::class => JitBooleanNot::class,
    ];

    public function test(Node $node)
    {
        return isset(self::$map[get_class($node)]) || $node instanceof AstUnaryMinus;
    }

    public function parse(Node $stmt, State $state)
    {
        $var = $state->parser->parseNode($stmt->expr, $state);

        if (isset(self::$map[get_class($stmt)])) {
            $class = self::$map[get_class($stmt)];
            $state->addVertex(new $class(
                $var,
                $result = new Variable($var->getType())
            ));

            return $result;
        } else {
            $state->addVertex(new JitBinaryOp(
                JitBinaryOp::MINUS,
                new Constant(0),
                $var,
                $result = new Variable()
            ));

            return $result;
        }
    }

}
