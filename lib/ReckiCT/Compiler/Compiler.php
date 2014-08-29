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
        $functions = $this->separateIrToFunctions($instructions);
        $callables = array();
        foreach ($functions as $name => $func) {
            $callables[$name] = $this->convertToCallable($func);
        }
        if (count($functions) === 1) {
            return $callables[$name];
        }

        return $callables;
    }

    abstract public function convertToCallable(array $instructions);

    protected function parseIr($ir)
    {
        $lines = explode("\n", $ir);
        $return = array();
        foreach ($lines as $line) {
            $return[] = explode(' ', $line);
        }

        return $return;
    }

    protected function separateIrToFunctions(array $instructions)
    {
        $functions = [
            '' => [],
        ];
        $currentFunction = '';
        foreach ($instructions as $row) {
            if ($row[0] === 'function') {
                $currentFunction = $row[1];
                $functions[$currentFunction] = [];
            } elseif ($row[0] === 'end') {
                $currentFunction = '';
                continue;
            }
            $functions[$currentFunction][] = $row;
        }
        if (empty($functions[''])) {
            // remove placeholder
            unset($functions['']);
        }

        return $functions;
    }

}
