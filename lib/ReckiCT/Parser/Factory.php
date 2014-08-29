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
 */

namespace ReckiCT\Parser;

class Factory
{
    public static function parser()
    {
        $parser = new Parser();
        $parser->addRule(new Rules\Assign());
        $parser->addRule(new Rules\BinaryOp());
        $parser->addRule(new Rules\BooleanAnd());
        $parser->addRule(new Rules\FunctionCall());
        $parser->addRule(new Rules\Goto_());
        $parser->addRule(new Rules\If_());
        $parser->addRule(new Rules\Label());
        $parser->addRule(new Rules\PreOp());
        $parser->addRule(new Rules\PostOp());
        $parser->addRule(new Rules\Return_());
        $parser->addRule(new Rules\Ternary());
        $parser->addRule(new Rules\UnaryOp());

        return $parser;
    }

}
