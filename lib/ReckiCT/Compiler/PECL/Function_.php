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
 * @subpackage PECL
 */

namespace ReckiCT\Compiler\PECL;

use ReckiCT\Jit;

class Function_
{

    protected $name;
    protected $params = [];
    protected $returnType;
    protected $code;

    public function __construct($name, array $ir) {
        $this->name = $name;
        $this->compile($ir);
    }

    public function getName() {
        return $this->name;
    }

    public function getCode() {
        return $this->code;
    }

    public function getHeader() {
        return "PHP_FUNCTION({$this->name});\n" . $this->getSignature() . ";\n";
    }

    public function getFunctionEntry() {
        return "\tPHP_FE({$this->name}, arginfo_{$this->name})\n";
    }

    public function getArgInfo() {
        $code = "ZEND_BEGIN_ARG_INFO_EX(arginfo_{$this->name}, 0, 0, " . count($func->params) . ")\n";
        foreach ($this->params as $param) {
            $code .= "\tZEND_ARG_INFO(0, {$param[0]})\n";
        }
        $code .= "ZEND_END_ARG_INFO()\n";
        return $code;
    }

    protected function compile(array $ir) {
        $this->parseIr($ir);
        $this->code .= $this->generateProxyFunction();
        $this->code .= $this->generateInternalFunction($ir);
    }

    protected function generateInternalFunction(array $ir) {
        
        $this->code .= "\n\n" . $this->getSignature() . " {\n";
        $this->code .= $this->buildFunction($ir);
        $this->code .= "}\n";
    }

    protected function getSignature() {
        $code = "static inline " . $this->returnType . " recki_if_{$this->name}(";
        foreach ($this->params as $param) {
            $code .= $this->convertToCType($param[1]) . ' ' . $param[0] . ', ';
        }
        $code .= "int *validReturn)";
        return $code;
    }

    protected function parseIr(array $ir) {
        $this->returnType = $this->convertToCType($ir[0][2]);
        $i = 1;
        while ('begin' != $ir[$i][0]) {
            $this->params[] = [
                $this->convertToCLabel($ir[$i][1]), 
                $ir[$i][2]
            ];
            $i++;
        }
    }

    protected function generateProxyFunction() {

        $code = "PHP_FUNCTION({$this->name}) {\n";
        if ($this->returnType != 'void') {
            $code .= "\t" . $this->returnType . " reckiretval;\n";
        }
        $code .= "\tint validReturn;\n";

        $zppType = '';
        $zppArgs = '';
        $callArgs = '';
        foreach ($this->params as $param) {
            $code .= "\t" . $this->convertToCType($param[1]) . ' ' . $param[0] . ";\n";
            $zppType .= $this->getZppFromType($param[1]);

            $callArgs .= $param[0] . ', ';
            if ($zppType == 's') {
                $zppArgs .= ', &' . $param[0] . '.string, &' . $param[0] . '.length';
            } else {
                $zppArgs .= ', &' . $param[0];
            }

        }
        $code .= "\tif (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, \"{$zppType}\"{$zppArgs}) == FAILURE) {\n\t\treturn;\n\t}\n";
        if ($this->returnType != 'void') {
            $code .= "\treckiretval = ";
        }
        $code .= "recki_if_{$this->name}($callArgs &validReturn);\n";
        if ($this->returnType != 'void') {
            $code .= "\tif (validReturn != SUCCESS) {\n\t\treturn;\n\t}\n";
            $code .= "\t" .$this->generateRetvalStatement($this->returnType);
        }
        $code .= "\n}\n";
        return $code;
    }

    protected function buildFunction(array $ir) {
        $code = '';
        $i = 1;
        $scope = [];
        while ('begin' != $ir[$i][0]) {
            $scope[$ir[$i][1]] = $this->convertToCLabel($ir[$i][1]);
            $i++;
        }
        
        $vars = [];
        $count = count($ir);
        while (++$i < $count) {
            $instruction = $ir[$i];
            $prefix = '';
            switch ($instruction[0]) {
                case 'var':
                    if ($instruction[2] === 'void') break;
                    $scope[$instruction[1]] = $this->convertToCLabel($instruction[1]);
                    $vars[$scope[$instruction[1]]] = $this->convertToCType($instruction[2]);

                    break;
                case 'const':
                    $scope[$instruction[1]] = $this->printConstant($instruction);
                    break;

                case '~':
                case '!':
                    $prefix = $instruction[0];
                case 'assign':
                    $code .= $scope[$instruction[2]] . '= ' . $prefix . $scope[$instruction[1]] . ";\n";
                    break;
                case 'return':
                    $code .= "*validReturn = SUCCESS;\n";
                    if (isset($instruction[1])) {
                        $code .= 'return ' . $scope[$instruction[1]] . ";\n";
                    } else {
                        $code .= "return;\n";
                    }
                    break;
                case 'label':
                    $code .= $this->convertToCLabel($instruction[1]) . ":\n";
                    break;
                case 'jump':
                    $code .= 'goto ' . $this->convertToCLabel($instruction[1]) . ";\n";
                    break;
                case 'jumpz':
                    $code .= 'if (!' . $scope[$instruction[1]] . ') { goto ' . $this->convertToCLabel($instruction[2]) . "; }\n";
                    break;
                case 'recurse':
                    $code .= $scope[$instruction[count($instruction) - 1]] . ' = recki_if_' . strtolower($obj->name) . '(';
                    for ($j = 1; $j < count($instruction) - 1; $j++) {
                        $code .= $scope[$instruction[$j]] . ', ';
                    }
                    $code .= "validReturn);\n";
                    $code .= "if (*validReturn != SUCCESS) {\n\t\treturn;\n}\n";
                    break;
                case 'functioncall':
                    if ($instruction[1] == 'strlen') {
                        $code .= $scope[$instruction[count($instruction) - 1]] . ' = ' . $scope[$instruction[2]] . '.length;';
                        break;
                    }
                    if (isset($scope[$instruction[count($instruction) - 1]])) {
                        $code .= $scope[$instruction[count($instruction) - 1]] . ' = ';
                    }
                    $code .= 'recki_if_' . strtolower($instruction[1]) . '(';
                    for ($j = 2; $j < count($instruction) - 1; $j++) {
                        $code .= $scope[$instruction[$j]] . ', ';
                    }
                    $code .= "validReturn);\n";
                    $code .= "if (*validReturn != SUCCESS) {\n\t\treturn;\n\t}\n";
                    break;
                case '+':
                case '-':
                case '*':
                case '/':
                case '&':
                case '|':
                case '^':
                case '>>':
                case '<<':
                case '==':
                case '!=':
                case '<':
                case '<=':
                case '>':
                case '>=':
                    $code .= $scope[$instruction[3]] . '=' . $scope[$instruction[1]] . $instruction[0] . $scope[$instruction[2]] . ";\n";
                    break;
                default:
                    throw new \RuntimeException("Unsupported compiler operation {$instruction[0]}");
            }
        }
        // Generate variable declarations
        foreach ($vars as $name => $type) {
            $code = $type . ' ' . $name . ";\n" . $code;
        }
        return preg_replace("(^(.))m", "\t$1", $code);
    }

    protected function convertToCType($what) {
        switch ($what) {
            case 'numeric':
            case 'long':
                return 'long';
            case 'double':
                return 'double';
            case 'string':
                return 'reckistring';
            case 'bool':
                return 'zend_bool';
            case 'void':
                return 'void';
        }
        throw new \RuntimeException("Unsupported C Type $what");
    }

    protected function convertToCLabel($what) {
        if ($what[0] === '$') {
            return str_replace('$', 'var', $what);
        } elseif ($what[0] === '@') {
            return str_replace('@', 'label', $what);
        }
        throw new \Exception("Not implemented: $what");
    }

    protected function printConstant(array $const) {
        switch ($const[2]) {
            case 'numeric';
            case 'int':
            case 'double':
                return $const[3];
            case 'bool':
                return (int) $const[3];
            case 'string':
                $val = base64_decode($const[3]);
                return '((reckistring){"' . addslashes($val) . '", ' . strlen($val) . '})';
        }
        throw new \RuntimeException("Unknown constant type {$const[2]} with value {$const[3]}");
    }

        protected function generateRetvalStatement($type) {
        switch ($type) {
            case 'long':
                return 'RETURN_LONG(reckiretval);';
            case 'double':
                return 'RETURN_DOUBLE(reckiretval);';
            case 'zend_bool':
                return 'RETURN_BOOL(reckiretval);';
            case 'reckistring':
                return 'RETURN_STRINGL(reckiretval.string, reckiretval.length, 1);';
        }
        throw new \RuntimeException('Retval Type Not Implemented: ' . $type);
    }

    protected function getZppFromType($type) {
        switch ($type) {
            case 'long':
                return 'l';
            case 'string':
                return 's';
            case 'zend_bool':
                return 'b';
            case 'double':
                return 'd';
        }
        throw new \RuntimeException('ZPP type not implemented: ' . $type);
    }

}