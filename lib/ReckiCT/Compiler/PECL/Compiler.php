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


    public function convertToCallable(array $instructions) {
        throw new \BadMethodCallException("Not implemented");
    }

    public function compile($ir, $name)
    {
        $instructions = $this->parseIr($ir);
        $functions = $this->separateIrToFunctions($instructions);

        $module = new Module($name);

        foreach ($functions as $name => $func) {
            $obj = new Function_($name, $func, $module);
            $module->addFunction($obj);
        }
        return $module->compile();
    }

}
