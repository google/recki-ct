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

use ReckiCT\Type;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Vertex\BinaryOp as JitBinaryOp;

use ReckiCT\Analyzer\OptimizerRule;

class BinaryOp implements OptimizerRule
{
    public function process(Vertex $vertex, Digraph $graph)
    {
        if ($vertex->getName() !== 'BinaryOp') {
            return false;
        }
        $result = $vertex->getResult();
        $rType = $result->getType();
        if ($rType->isUnknown() || $rType->getType() === Type::TYPE_NUMERIC) {
            $typePair = [$vertex->getA()->getType()->getType(), $vertex->getB()->getType()->getType()];
            $newType = Type::TYPE_UNKNOWN;
            switch ($vertex->getKind()) {
                case JitBinaryOp::CONCAT:
                    $newType = Type::TYPE_STRING;
                    break;
                case JitBinaryOp::PLUS:
                case JitBinaryOp::MINUS:
                case JitBinaryOp::MUL:
                    switch ($typePair) {
                        case [Type::TYPE_LONG, Type::TYPE_LONG]:
                            $newType = Type::TYPE_NUMERIC;
                            break;
                        case [Type::TYPE_DOUBLE, Type::TYPE_LONG]:
                        case [Type::TYPE_LONG, Type::TYPE_DOUBLE]:
                        case [Type::TYPE_DOUBLE, Type::TYPE_NUMERIC]:
                        case [Type::TYPE_NUMERIC, Type::TYPE_DOUBLE]:
                        case [Type::TYPE_DOUBLE, Type::TYPE_DOUBLE]:
                            $newType = Type::TYPE_DOUBLE;
                            break;
                        case [Type::TYPE_NUMERIC, Type::TYPE_NUMERIC]:
                        case [Type::TYPE_NUMERIC, Type::TYPE_LONG]:
                        case [Type::TYPE_LONG, Type::TYPE_NUMERIC]:
                            $newType = Type::TYPE_NUMERIC;
                            break;
                        default:
                            if (in_array(Type::TYPE_DOUBLE, $typePair)) {
                                $newType = Type::TYPE_DOUBLE;
                            } else {
                                $newType = Type::TYPE_NUMERIC;
                            }
                    }
                    break;
                case JitBinaryOp::DIV:
                    $newType = Type::TYPE_DOUBLE;
                    break;
                case JitBinaryOp::EQUAL:
                case JitBinaryOp::NOT_EQUAL:
                case JitBinaryOp::IDENTICAL:
                case JitBinaryOp::NOT_IDENTICAL:
                case JitBinaryOp::GREATER:
                case JitBinaryOp::GREATER_EQUAL:
                case JitBinaryOp::SMALLER:
                case JitBinaryOp::SMALLER_EQUAL:
                    $newType = Type::TYPE_BOOLEAN;
                    break;
                case JitBinaryOp::BITWISE_AND:
                case JitBinaryOp::BITWISE_OR:
                case JitBinaryOp::BITWISE_XOR:
                    if ($typePair === [Type::TYPE_STRING, Type::TYPE_STRING]) {
                        $newType = Type::TYPE_STRING;
                        break;
                    }
                    // Fallthrough intentional
                case JitBinaryOp::SHIFT_LEFT:
                case JitBinaryOp::SHIFT_RIGHT:
                    $newType = Type::TYPE_LONG;
                    break;
            }
            if ($newType !== Type::TYPE_UNKNOWN && $newType !== $rType->getType()) {
                $result->setType(new Type($newType));

                return true;
            }
        }

        return false;
    }

}
