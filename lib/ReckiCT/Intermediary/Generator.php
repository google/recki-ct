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
 * @package Intermediary
 */

namespace ReckiCT\Intermediary;

use ReckiCT\Graph\Constant;
use ReckiCT\Graph\Vertex;

class Generator
{
    public function generateFunction($name, Vertex\Function_ $func)
    {
        $state = (object) [
            'scope' => new \SplObjectStorage(),
            'labels' => new \SplObjectStorage(),
            'varidx' => 0,
            'labelidx' => 0,
            'seen' => new \SplObjectStorage(),
            'graph' => $func->getGraph(),
            'constants' => [],
        ];

        $body = $this->generate($func, $state);
        $replace = "";
        if ($state->constants) {
            $replace = implode("\n", $state->constants) . "\n";
        }
        $body = str_replace("--constants--\n", $replace, $body);

        return 'function ' . $name . ' ' . $func->getReturnType() . "\n" . $body . "end";
    }

    public function generate(Vertex $vertex, \StdClass $state)
    {
        if (isset($state->seen[$vertex])) {
            return '';
        }
        $state->seen[$vertex] = true;

        $output = $this->getTypedOutput($vertex, $state);

        if ($vertex instanceof Vertex\JumpZ) {
            $output .= ' ' . $this->makeLabel($vertex->getTarget(), $state) . "\n";
            $out = [];
            foreach ($state->graph->successorsOf($vertex) as $next) {
                $out[] = $next;
            }
            if ($out[0] === $vertex->getTarget()) {
                $output .= $this->generate($out[1], $state);
                $output .= $this->generate($out[0], $state);
            } else {
                $output .= $this->generate($out[0], $state);
                $output .= $this->generate($out[1], $state);
            }
        } elseif (!$vertex instanceof Vertex\Return_) {
            if ($output) {
                $output .= "\n";
            }
            foreach ($state->graph->successorsOf($vertex) as $next) {
                $output .= $this->generate($next, $state);
            }
        } else {
            $output .= "\n";
        }

        return $output;
    }

    public function getTypedOutput(Vertex $vertex, \StdClass $state)
    {
        list ($vars, $varStub) = $this->getVarStub($vertex, $state);

        $output = strtolower($vertex->getName());
        if ($vertex instanceof Vertex\BinaryOp) {
            $output = $vertex->getKind();
        } elseif ($vertex instanceof Vertex\BooleanNot) {
            $output = '!';
        } elseif ($vertex instanceof Vertex\BitwiseNot) {
            $output = '~';
        } elseif ($vertex instanceof Vertex\NoOp) {
            return 'label ' . $this->makeLabel($vertex, $state);
        } elseif ($vertex instanceof Vertex\Jump) {
            foreach ($state->graph->successorsOf($vertex) as $next) {}
            if (isset($state->seen[$next])) {
                return 'jump ' . $this->makeLabel($next, $state);
            } else {
                return '';
            }
        } elseif ($vertex instanceof Vertex\Function_) {
            return $varStub . "begin\n--constants--";
        } elseif ($vertex instanceof Vertex\FunctionCall) {
            if ($vertex->isSelfRecursive()) {
                $output = 'recurse';
            } else {
                $output = 'functioncall ' . $vertex->getFunctionName();
            }
        } elseif ($vertex instanceof Vertex\End) {
            // generate a default return for every branch!
            unset($state->seen[$vertex]);

            return 'return';
        }

        return $varStub . $output . $vars;
    }

    public function getVarStub(Vertex $vertex, \StdClass $state)
    {
        $output = '';
        $varStub = '';
        foreach ($vertex->getVariables() as $var) {
            if (!isset($state->scope[$var])) {
                $state->scope[$var] = ++$state->varidx;
                if ($var instanceof Constant) {
                    $value = $var->getValue();
                    if ('string' == (string) $var->getType()) {
                        $value = base64_encode($value);
                    }
                    $state->constants[] = 'const $' . $state->scope[$var] . ' ' . $var->getType() . ' ' . $value;
                } elseif ($vertex instanceof Vertex\Function_) {
                    $varStub .= 'param $' . $state->scope[$var] . ' ' . $var->getType() . "\n";
                } else {
                    $varStub .= 'var $' . $state->scope[$var] . ' ' . $var->getType() . "\n";
                }
            }
            $output .= ' $' . $state->scope[$var];
        }

        return [$output, $varStub];
    }

    public function makeLabel(Vertex\NoOp $next, \StdClass $state)
    {
        if (!isset($state->labels[$next])) {
            $state->labels[$next] = ++$state->labelidx;
        }

        return '@' . $state->labels[$next];
    }

}
