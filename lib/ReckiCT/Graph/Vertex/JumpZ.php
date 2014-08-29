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

use ReckiCT\Graph\Traits;
use ReckiCT\Graph\Vertex;
use ReckiCT\Graph\Variable;

class JumpZ extends Vertex
{
    use Traits\Value;

    protected $target;

    public function __construct(Vertex $target, Variable $value)
    {
        $this->target = $target;
        $this->value = $value;
    }

    public function __toString()
    {
        return 'jumpz(' . $this->value . ')';
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget(Vertex $target)
    {
        $this->target = $target;
    }

    public function getVariables()
    {
        return [$this->value];
    }

    public function replaceVariable(Variable $from, Variable $to)
    {
        if ($this->value === $from) {
            $this->value = $to;
        }
    }

}
