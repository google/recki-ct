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
 */

namespace ReckiCT\Compiler;

abstract class Compiler
{
    public function compile($ir)
    {
        $instructions = $this->parseIr($ir);
        $elements = $this->separateIr($instructions);
        $results = array();
        foreach ($elements['functions'] as $name => $func) {
            $results[] = $this->convertToCallable($func);
        }
        foreach ($elements['classes'] as $class) {
            $results[] = $this->convertToClass($class);
        }


        return $results;
    }

    abstract public function convertToCallable(array $instructions);

    abstract public function convertToClass(\StdClass $instructions);

    protected function parseIr($ir)
    {
        $lines = explode("\n", $ir);
        $return = array();
        foreach ($lines as $line) {
            $return[] = explode(' ', $line);
        }

        return $return;
    }

    protected function separateIr(array $instructions)
    {
        $elements = [
            "classes" => [],
            "functions" => [],
        ];
        $mode = "";
        $currentFunction = '';
        $currentClass = '';
        $count = count($instructions);
        for ($i = 0; $i < $count; $i++) {
            $row = $instructions[$i];

            switch ($row[0]) {
                case 'function':
                    $mode = "function";
                    $currentFunction = $row[1];
                    $elements['functions'][$currentFunction] = [];    
                    break;
                case 'endfunction':
                    $currentFunction = '';
                    $mode = '';
                    continue 2;
                case 'class':
                    $mode = 'class';
                    $currentClass = $row[1];
                    $elements['classes'][$currentClass] = new \StdClass;
                    $elements['classes'][$currentClass]->name = $row[1];
                    $elements['classes'][$currentClass]->properties = [];
                    $elements['classes'][$currentClass]->extends = null;
                    $elements['classes'][$currentClass]->implements = [];
                    $elements['classes'][$currentClass]->methods = [];
                    break;
                case 'endclass':
                    $currentClass = '';
                    $mode = '';
                    continue 2;
            }

            if ($mode == "function") {
                $elements['functions'][$currentFunction][] = $row;
            } elseif ($mode == "class") {
                switch ($row[0]) {
                    case 'property':
                        $elements['classes'][$currentClass]->properties[] = $row;
                        break;        
                    case 'extends':
                        $elements['classes'][$currentClass]->extends = $row;
                        break;
                    case 'implements':
                        $elements['classes'][$currentClass]->implements[] = $row;
                        break;
                    case 'method':
                        $method = [];
                        $name = $row[1];
                        do {
                            $method[] = $instructions[$i++];
                        } while ($i < $count && $instructions[$i][0] != 'endmethod');
                        $elements['classes'][$currentClass]->methods[$name] = $method;
                        break;
                }
            }
        }
        if (empty($elements['functions'][''])) {
            // remove placeholder
            unset($elements['functions']['']);
        }
        if (empty($elements['classes'][''])) {
            // remove placeholder
            unset($elements['classes']['']);
        }
        return $elements;
    }

}
