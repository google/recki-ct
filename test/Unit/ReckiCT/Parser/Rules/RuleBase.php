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
 * @category Tests
 * @package Parser
 * @subpackage Rules
 */

namespace ReckiCT\Parser\Rules;

use PHPUnit_Framework_TestCase as TestCase;
use ReckiCT\Parser\State;
use ReckiCT\Parser\Parser;
use ReckiCT\Graph\Vertex\NoOp;

use Gliph\Graph\DirectedAdjacencyList;

use Mockery as m;

abstract class RuleBase extends TestCase
{
    protected $state;

    protected function setUp()
    {
        $this->state = new State(
            m::mock(Parser::class),
            new DirectedAdjacencyList()
        );
        $this->state->last = new NoOp();
    }

}
