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
 * @package Compiler
 * @subpackage JitFu
 */

namespace ReckiCT\Compiler\JitFu;

use JITFU\Func as JitFunction;
use JITFU\Context as JitContext;
use JITFU\Signature as JitSignature;
use JITFU\Type as JitType;
use JITFU\Value as JitValue;

use ReckiCT\Jit;
use ReckiCT\Compiler\Compiler as BaseCompiler;

class Compiler extends BaseCompiler
{
    protected $constants = [];

    protected static $binaryOps = [
        '+' => 'doAdd',
        '-' => 'doSub',
        '*' => 'doMul',
        '/' => 'doDiv',
        '%' => 'doRem',
        '==' => 'doEq',
        '===' => 'doEq',
        '!=' => 'doNe',
        '!==' => 'doNe',
        '<' => 'doLt',
        '<=' => 'doLe',
        '>' => 'doGt',
        '>=' => 'doGe',
        '&' => 'doAnd',
        '|' => 'doOr',
        '^' => 'doXor',
        '<<' => 'doShl',
        '>>' => 'doShr',
    ];

    protected $context;
    protected $types = [];
    protected $jit;
    protected $compileCache = [];

    public function __construct(Jit $jit)
    {
        $this->jit = $jit;
        $this->context = new JitContext();
    }

    public function convertToCallable(array $instructions)
    {
        $func = $instructions[0];
        if (isset($this->compileCache[$func[1]])) {
            return $this->compileCache[$func[1]];
        }
        $args = [];
        $argNames = [];
        $i = 1;
        while ('begin' != $instructions[$i][0]) {
            $argNames[] = $instructions[$i][1];
            $args[] = $this->createType($instructions[$i][2]);
            $i++;
        }
        $signature = new JitSignature($this->createType($func[2]), $args);
        $function = new JitFunction($this->context, $signature);
        $this->compileCache[$func[1]] = $function;
        $ctx = (object) [
            'compiler' => $this,
            'function' => $function,
            'instructions' => $instructions,
            'i' => $i,
            'scope' => [
                -1 => new JitValue($function, -1, $this->createType('long')),
            ],
            'argNames' => $argNames,
            'labels' => [],
        ];
        $function->implement(function (array $args) use ($ctx) {
            foreach ($args as $k => $v) {
                $ctx->scope[$ctx->argNames[$k]] = $v;
            }
            $count = count($ctx->instructions);
            while (++$ctx->i < $count) {
                $ctx->compiler->compileInstruction($ctx->instructions[$ctx->i], $ctx);
            }
            $ctx->labels = [];
            $ctx->scope = [];
        });

        return $function;
    }

    public function compileInstruction(array $instruction, \StdClass $ctx)
    {
        switch ($instruction[0]) {
            case 'free':
                // ignore free statements
                return;
            case 'const':
                $value = null;
                switch ($instruction[2]) {
                    case 'bool':
                    case 'long':
                    case 'numeric':
                        $value = (int) $instruction[3];
                        break;
                    case 'double':
                        $value = (double) $instruction[3];
                        break;
                    case 'string':
                        $value = base64_decode($instruction[3]);
                        break;
                    default:
                        throw new \RuntimeException('Invalid constant type: ' . $instruction[2]);
                }
                $ctx->scope[$instruction[1]] = new JitValue($ctx->function, $value, $this->createType($instruction[2]));

                return;
            case 'var':
                $ctx->scope[$instruction[1]] = new JitValue($ctx->function, $this->createType($instruction[2]));

                return;
            case 'assign':
                $ctx->function->doStore($ctx->scope[$instruction[2]], $ctx->scope[$instruction[1]]);

                return;
            case 'label':
                if (isset($ctx->labels[$instruction[1]])) {
                    $ctx->function->doLabel($ctx->labels[$instruction[1]]);
                } else {
                    $ctx->labels[$instruction[1]] = $ctx->function->doLabel();
                }

                return;
            case 'jump':
                if (isset($ctx->labels[$instruction[1]])) {
                    $ctx->function->doBranch($ctx->labels[$instruction[1]]);
                } else {
                    $ctx->labels[$instruction[1]] = $ctx->function->doBranch();
                }

                return;
            case 'jumpz':
                $var = $ctx->scope[$instruction[1]];
                if (isset($ctx->labels[$instruction[2]])) {
                    $ctx->function->doBranchIfNot($var, $ctx->labels[$instruction[2]]);
                } else {
                    $ctx->labels[$instruction[2]] = $ctx->function->doBranchIfNot($var);
                }

                return;
            case 'return':
                if (isset($instruction[1])) {
                    $ctx->function->doReturn($ctx->scope[$instruction[1]]);
                } else {
                    $ctx->function->doDefaultReturn();
                }

                return;
            case '!':
                $this->storeResultVar(
                    $instruction[2],
                    $ctx->function->doToNotBool($ctx->scope[$instruction[1]]),
                    $ctx
                );

                return;
            case '~':
                $this->storeResultVar(
                    $instruction[2],
                    $ctx->function->doXor(
                        $ctx->scope[$instruction[1]],
                        $ctx->scope[-1]
                    ),
                    $ctx
                );

                return;
            case 'recurse':
                $args = [];
                for ($i = 1, $n = count($instruction); $i < $n - 1; $i++) {
                    $args[] = $ctx->scope[$instruction[$i]];
                }
                $this->storeResultVar(
                    $instruction[$n - 1],
                    $ctx->function->doCall($ctx->function, $args),
                    $ctx
                );

                return;
            case 'functioncall':
                $name = $instruction[1];
                $args = [];
                for ($i = 2, $n = count($instruction); $i < $n - 1; $i++) {
                    $args[] = $ctx->scope[$instruction[$i]];
                }
                $this->storeResultVar(
                    $instruction[$n - 1],
                    $this->resolveFunctionCall($ctx, $name, $args),
                    $ctx
                );

                return;

        }
        if (isset(self::$binaryOps[$instruction[0]])) {
            $op = self::$binaryOps[$instruction[0]];
            $this->storeResultVar(
                $instruction[3],
                $ctx->function->$op(
                    $ctx->scope[$instruction[1]],
                    $ctx->scope[$instruction[2]]
                ),
                $ctx
            );

            return;
        }
        throw new \RuntimeException('Invalid op found! ' . $instruction[0]);
    }

    public function storeResultVar($var, JitValue $value, \StdClass $ctx)
    {
        if ('var' === $ctx->instructions[$ctx->i-1][0] && $ctx->instructions[$ctx->i-1][1] === $var) {
            $ctx->scope[$var] = $value;
        } else {
            $ctx->function->doStore($ctx->scope[$var], $value);
        }
    }

    public function createType($type)
    {
        if (!isset($this->types[$type])) {
            $jittype = null;
            switch ($type) {
                case 'bool':
                case 'numeric':
                case 'long':
                    $jittype = JitType::of(JitType::long);
                    break;
                case 'double':
                    $jittype = JitType::of(JitType::double);
                    break;
                case 'string':
                    $jittype = JitType::of(JitType::string);
                    break;
                case 'void':
                    $jittype = JitType::of(JitType::void);
            }
            if (!$jittype && substr($type, -2) === '[]') {
                $subtype = $this->createType(substr($type, 0, -2));
                $jittype = new JitType($subtype, true);
            }
            if (!$jittype) {
                throw new \RuntimeException('Unknown type encountered: ' . $type);
            }
            $this->types[$type] = $jittype;
        }

        return $this->types[$type];
    }

    public function resolveFunctionCall($ctx, $name, array $args) {
        switch (strtolower($name)) {
            case 'strlen':
            case 'count':
                if (count($args) !== 1) {
                    throw new \RuntimeException("Invalid arguments supplied for strlen()");
                }
                return $ctx->function->doSize($args[0]);
        }
        return $ctx->function->doCall($this->jit->compileFunctionJitFu($name), $args);
    }

}
