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
use ReckiCT\Compiler\Compiler as BaseCompiler;

class Compiler extends BaseCompiler
{


    public function convertToCallable(array $instructions) {}

    protected static $binaryOps = [
        '+',
        '-',
        '*',
        '/',
        '%',
        '==',
        '===',
        '!=',
        '!==',
        '<',
        '<=',
        '>',
        '>=',
        '&',
        '|',
        '^',
        '<<',
        '>>',
    ];

    public function compile($ir)
    {
        $moduleName = $this->generateModuleName();
        $instructions = $this->parseIr($ir);
        $functions = $this->separateIrToFunctions($instructions);

        $funcs = array();        
        foreach ($functions as $name => $func) {
            $obj = new Function_;
            $obj->name = $name;
            $this->buildFunction($func, $obj);
            $funcs[] = $obj;
        }
        $ext = [
            'config.m4' => $this->generateConfigFile($moduleName),
            'php_' . $moduleName . '.h' => $this->generateHeaderFile($moduleName),
            $moduleName . '.c' => $this->generateCFile($moduleName, $funcs),
        ];
        return $ext;
    }

    protected function generateConfigFile($moduleName) {
        $upperName = strtoupper($moduleName);
        return <<<"EOF"
            PHP_ARG_ENABLE({$moduleName}, enable {$moduleName} support, 
                    [ --enable-{$moduleName}       Enable {$moduleName} support],yes)
            AC_MSG_CHECKING([Checking for supported PHP versions])
            PHP_{$moduleName}_FOUND_VERSION=`\${PHP_CONFIG} --version`
            PHP_{$moduleName}_FOUND_VERNUM=`echo "\${PHP_{$upperName}_FOUND_VERSION}" | \$AWK 'BEGIN { FS = "."; } { printf "%d", ([\$]1 * 100 + [\$]2) * 100 + [\$]3;}'`
            if test "\$PHP_{$upperName}_FOUND_VERNUM" -lt "50300"; then
                AC_MSG_ERROR([not supported. Need a PHP version >= 5.3.0 (found \$PHP_{$moduleName}_FOUND_VERSION)])
            else
                AC_MSG_RESULT([supported (\$PHP_{$upperName}_FOUND_VERSION)])
            fi
            AC_DEFINE(HAVE_{$upperName}, 1, [Compile with {$moduleName} support])
            PHP_NEW_EXTENSION({$moduleName}, {$moduleName}.c, \$ext_shared)
EOF;
    }

    protected function generateHeaderFile($moduleName) {
        $upperName = strtoupper($moduleName);
        $code = "#ifndef PHP_{$upperName}_H\n";
        $code .= "#define PHP_{$upperName}_H 1\n";
        $code .= "#define PHP_{$upperName}_VERSION \"1.0\"\n";
        $code .= "#define PHP_{$upperName}_EXTNAME \"{$moduleName}\"\n";
        $code .= "extern zend_module_entry {$moduleName}_module_entry;\n";
        $code .= "PHP_MINFO_FUNCTION({$moduleName});\n";
        $code .= "#endif";
        return $code;
    }

    protected function generateCFile($moduleName, array $funcs) {
        $upperName = strtoupper($moduleName);
        // generate header
        $code = "#ifdef HAVE_CONFIG_H\n";
        $code .= "#include \"config.h\"\n";
        $code .= "#endif\n";
        $code .= "#include \"php.h\"\n";
        $code .= "#include \"php_{$moduleName}.h\"\n";

        $code .= "typedef struct _reckistring { char *string; int length; } reckistring;\n";
        
        foreach ($funcs as $func) {
            $code .= "PHP_FUNCTION({$func->name});\n";
            $code .= 'static inline ' . $this->generateInternalFuncSignature($func) . ";\n";
        }
        
        foreach ($funcs as $func) {
            $code .= 'static inline ' . $this->generateInternalFuncSignature($func) . "{\n{$func->code}\n}\n";
            $code .= "PHP_FUNCTION({$func->name}) {\n";
            if ($func->returnType != 'void') {
                $code .= $func->returnType . " reckiretval;\n";
            }
            $code .= "int validReturn;\n";
            $zppType = '';
            $zppArgs = '';
            $callArgs = '';
            foreach ($func->params as $param) {
                $code .= $this->convertToCType($param[1]) . ' ' . $param[0] . ";\n";
                $zppType .= $this->getZppFromType($param[1]);

                $callArgs .= $param[0] . ', ';
                if ($zppType == 's') {
                    $zppArgs .= ', &' . $param[0] . '.string, &' . $param[0] . '.length';
                } else {
                    $zppArgs .= ', &' . $param[0];
                }

            }
            $code .= "if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, \"{$zppType}\"{$zppArgs}) == FAILURE) {return;}\n";
            if ($func->returnType != 'void') {
                $code .= "reckiretval = ";
            }
            $code .= "recki_if_{$func->name}($callArgs &validReturn);\n";
            if ($func->returnType != 'void') {
                $code .= "if (validReturn != SUCCESS) { return; }\n";
                $code .= $this->generateRetvalStatement($func->returnType);
            }
            $code .= "\n}\n";

        }

        foreach ($funcs as $func) {
            // arginfo
            $code .= "ZEND_BEGIN_ARG_INFO_EX(arginfo_{$func->name}, 0, 0, " . count($func->params) . ")\n";
            foreach ($func->params as $param) {
                $code .= "ZEND_ARG_INFO(0, {$param[0]})\n";
            }
            $code .= "ZEND_END_ARG_INFO()\n";
        }

        $code .= "zend_function_entry {$moduleName}_functions[] = {\n";
        foreach ($funcs as $func) {
            $code .= "PHP_FE({$func->name}, arginfo_{$func->name})\n";
        }
        $code .= "{NULL, NULL, NULL}\n};\n";
        $code .= "zend_module_entry {$moduleName}_module_entry = {\n";
        $code .= "STANDARD_MODULE_HEADER,
                    PHP_{$upperName}_EXTNAME,
                    {$moduleName}_functions,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    PHP_MINFO({$moduleName}),
                    PHP_{$upperName}_VERSION,
                    STANDARD_MODULE_PROPERTIES";
        $code .= "};\n";
        $code .= "PHP_MINFO_FUNCTION({$moduleName}) {\n";
        $code .= "php_info_print_table_start();\n";
        $code .= "php_info_print_table_row(2, \"{$moduleName} support\", \"Emulated\");\n";
        $code .= "php_info_print_table_end();\n";
        $code .= "};\n";

        $code .= "#ifdef COMPILE_DL_{$upperName}\n";
        $code .= "ZEND_GET_MODULE($moduleName)\n";
        $code .= "#endif\n";

        return $code;

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

    protected function generateInternalFuncSignature($func) {
        $code = $func->returnType . " recki_if_{$func->name}(";
        foreach ($func->params as $param) {
            $code .= $this->convertToCType($param[1]) . ' ' . $param[0] . ', ';
        }
        $code .= "int *validReturn)";
        return $code;
    }

    protected function generateModuleName() {
        return 'recki' . mt_rand() . mt_rand() . mt_rand() . mt_rand();
    }

    protected function buildFunction(array $func, $obj) {
        $obj->returnType = $this->convertToCType($func[0][2]);
        $code = '';
        $i = 1;
        $scope = [];
        while ('begin' != $func[$i][0]) {
            $scope[$func[$i][1]] = $this->convertToCLabel($func[$i][1]);
            $obj->params[] = [$scope[$func[$i][1]], $func[$i][2]];
            $i++;
        }
        
        $vars = [];
        while (++$i < count($func)) {
            $prefix = '';
            switch ($func[$i][0]) {
                case 'var':
                    if ($func[$i][2] === 'void') break;
                    $scope[$func[$i][1]] = $this->convertToCLabel($func[$i][1]);
                    $vars[$scope[$func[$i][1]]] = $this->convertToCType($func[$i][2]);

                    break;
                case 'const':
                    $scope[$func[$i][1]] = $this->printConstant($func[$i]);
                    break;

                case '~':
                case '!':
                    $prefix = $func[$i][0];
                case 'assign':
                    $code .= $scope[$func[$i][2]] . '= ' . $prefix . $scope[$func[$i][1]] . ";\n";
                    break;
                case 'return':
                    $code .= "*validReturn = SUCCESS;\n";
                    if (isset($func[$i][1])) {
                        $code .= 'return ' . $scope[$func[$i][1]] . ";\n";
                    } else {
                        $code .= "return;\n";
                    }
                    break;
                case 'label':
                    $code .= $this->convertToCLabel($func[$i][1]) . ":\n";
                    break;
                case 'jump':
                    $code .= 'goto ' . $this->convertToCLabel($func[$i][1]) . ";\n";
                    break;
                case 'jumpz':
                    $code .= 'if (!' . $scope[$func[$i][1]] . ') { goto ' . $this->convertToCLabel($func[$i][2]) . "; }\n";
                    break;
                case 'recurse':
                    $code .= $scope[$func[$i][count($func[$i]) - 1]] . ' = recki_if_' . strtolower($obj->name) . '(';
                    for ($j = 1; $j < count($func[$i]) - 1; $j++) {
                        $code .= $scope[$func[$i][$j]] . ', ';
                    }
                    $code .= "validReturn);\n";
                    $code .= "if (*validReturn != SUCCESS) { return; }\n";
                    break;
                case 'functioncall':
                    if ($func[$i][1] == 'strlen') {
                        $code .= $scope[$func[$i][count($func[$i]) - 1]] . ' = ' . $scope[$func[$i][2]] . '.length;';
                        break;
                    }
                    if (isset($scope[$func[$i][count($func[$i]) - 1]])) {
                        $code .= $scope[$func[$i][count($func[$i]) - 1]] . ' = ';
                    }
                    $code .= 'recki_if_' . strtolower($func[$i][1]) . '(';
                    for ($j = 2; $j < count($func[$i]) - 1; $j++) {
                        $code .= $scope[$func[$i][$j]] . ', ';
                    }
                    $code .= "validReturn);\n";
                    $code .= "if (*validReturn != SUCCESS) { return; }\n";
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
                    $code .= $scope[$func[$i][3]] . '=' . $scope[$func[$i][1]] . $func[$i][0] . $scope[$func[$i][2]] . ";\n";
                    break;
                default:
                    throw new \RuntimeException("Unsupported compiler operation {$func[$i][0]}");
            }
        }
        foreach ($vars as $name => $type) {
            $code = $type . ' ' . $name . ";\n" . $code;
        }
        $obj->code = $code;
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

}
