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

use ReckiCT\Analyzer\OptimizerRule;

class BooleanNot implements OptimizerRule
{
    public function process(Vertex $vertex, Digraph $graph)
    {
        if ($vertex->getName() === 'BooleanNot') {
            if ($vertex->getResult()->getType()->isUnknown() && !$vertex->getValue()->getType()->isUnknown()) {
                $vertex->getResult()->setType(new Type(Type::TYPE_BOOLEAN));

                return true;
            }
        }

        return false;
    }

}
