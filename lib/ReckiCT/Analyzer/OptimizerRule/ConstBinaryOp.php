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
 * @subpackage OptimizerRule
 */

namespace ReckiCT\Analyzer\OptimizerRule;

use Gliph\Graph\Digraph;

use ReckiCT\Graph\Constant;

use ReckiCT\Graph\Helper;

use ReckiCT\Graph\Vertex;

use ReckiCT\Analyzer\OptimizerRule;

use ReckiCT\Graph\Vertex\Assign as JitAssign;
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;

class ConstBinaryOp implements OptimizerRule
{
    public function process(Vertex $vertex, Digraph $graph)
    {
        if ($vertex instanceof JitBinaryOp && $vertex->getA() instanceof Constant && $vertex->getB() instanceof Constant) {
            $ret = null;
            switch ($vertex->getKind()) {
                case JitBinaryOp::CONCAT:
                    $ret = $vertex->getA()->getValue() . $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::PLUS:
                    $ret = $vertex->getA()->getValue() + $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::MINUS:
                    $ret = $vertex->getA()->getValue() - $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::MUL:
                    $ret = $vertex->getA()->getValue() * $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::DIV:
                    $ret = $vertex->getA()->getValue() / $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::MOD:
                    $ret = $vertex->getA()->getValue() % $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::EQUAL:
                    $ret = $vertex->getA()->getValue() == $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::NOT_EQUAL:
                    $ret = $vertex->getA()->getValue() != $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::IDENTICAL:
                    $ret = $vertex->getA()->getValue() == $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::NOT_IDENTICAL:
                    $ret = $vertex->getA()->getValue() !== $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::GREATER:
                    $ret = $vertex->getA()->getValue() > $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::GREATER_EQUAL:
                    $ret = $vertex->getA()->getValue() >= $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::SMALLER:
                    $ret = $vertex->getA()->getValue() < $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::SMALLER_EQUAL:
                    $ret = $vertex->getA()->getValue() <= $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::BITWISE_AND:
                    $ret = $vertex->getA()->getValue() & $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::BITWISE_OR:
                    $ret = $vertex->getA()->getValue() | $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::BITWISE_XOR:
                    $ret = $vertex->getA()->getValue() ^ $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::SHIFT_LEFT:
                    $ret = $vertex->getA()->getValue() << $vertex->getB()->getValue();
                    break;
                case JitBinaryOp::SHIFT_RIGHT:
                    $ret = $vertex->getA()->getValue() >> $vertex->getB()->getValue();
                    break;
            }
            // replace binary op with assign op
            Helper::replace($vertex, new JitAssign(new Constant($ret), $vertex->getResult()), $graph);

            return true;
        }

        return false;
    }

}
