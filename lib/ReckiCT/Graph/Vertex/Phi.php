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
 * @package Graph
 * @subpackage Vertex
 */

namespace ReckiCT\Graph\Vertex;

use ReckiCT\Graph\Assignment;
use ReckiCT\Graph\Traits;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Variable;

class Phi extends Vertex implements Assignment
{
    use Traits\Assignment;

    protected $values;

    public function __construct(Variable $result)
    {
        $this->values = new \SplObjectStorage();
        $this->setResult($result);
    }

    public function __toString()
    {
        return $this->result . ' = Φ(' . implode(', ', iterator_to_array($this->values)) . ')';
    }

    public function addValue(Variable $value)
    {
        if ($value !== $this->result) {
            $this->values[$value] = $value;
        }
    }

    public function removeValue(Variable $value)
    {
        if ($this->values->contains($value)) {
            $this->values->detach($value);
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getVariables()
    {
        return array_merge([$this->result], iterator_to_array($this->values));
    }

    public function replaceVariable(Variable $from, Variable $to)
    {
        if ($this->values->contains($from)) {
            $this->removeValue($from);
            $this->addValue($to);
        }
    }

}
