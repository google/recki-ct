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

use ReckiCT\Graph\Helper;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Assignment;

use ReckiCT\Analyzer\OptimizerRule;

class Phi implements OptimizerRule
{
    public function process(Vertex $vertex, Digraph $graph)
    {
        if ($vertex instanceof Vertex\Phi) {
            $types = [];
            foreach ($vertex->getValues() as $value) {
                $types[] = (string) $value->getType();
            }
            $types = array_unique($types);
            if ($vertex->getResult()->getType()->isUnknown()) {
                $type = null;
                $setAll = false;
                if (count($types) === 1 && $types[0] !== 'unknown') {
                    $type = Type::normalizeType($types[0]);
                } elseif (count($types) === 2 && in_array('long', $types) && in_array('numeric', $types)) {
                    $type = new Type(Type::TYPE_LONG);
                    $setAll = true;
                } elseif (count($types) === 2 && in_array('double', $types) && in_array('numeric', $types)) {
                    $type = new Type(Type::TYPE_DOUBLE);
                    $setAll = true;
                } elseif (count($types) === 2 && in_array('bool', $types) && in_array('numeric', $types)) {
                    $type = new Type(Type::TYPE_BOOLEAN);
                }
                if ($type) {
                    $vertex->getResult()->setType($type);
                    if ($setAll) {
                        foreach ($vertex->getValues() as $value) {
                            $value->setType($type);
                        }
                    }

                    return true;
                }
            }
            if (count($vertex->getValues()) === 1) {
                // remove phi node
                list ($val) = iterator_to_array($vertex->getValues());
                $result = $vertex->getResult();
                foreach ($graph->vertices() as $vtx) {
                    $vtx->replaceVariable($result, $val);
                    if ($vtx instanceof Assignment && $vtx->getResult() === $result) {
                        $vtx->setResult($val);
                    }
                }
                Helper::remove($vertex, $graph);

                return true;
            }
        }

        return false;
    }

}
