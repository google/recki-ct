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

use PhpParser\Node;

/**
 * This interface represents an arbitrary compiler rule.
 *
 * When the parser encounters a PhpParser\Node instance, it will attempt
 * to find a rule that can parse it. It will iterate through all rules and
 * execute the `test($node)` method on it. If the test returns true, the node
 * will be parsed with that rule. Whatever the rule returns from `parse()`
 * will be returned by the parser.
 *
 * @api
 */
interface Rule
{

    /**
     * Test the node to see if this rule can parse it
     *
     * @param \PhpParser\Node $node The node to test against the rule.
     *
     * @return boolean True if the test passes
     */
    public function test(Node $node);

    /**
     * Parse the node using this rule.
     *
     * This assumes that the test passes. The rule class shouldn't need to re-check.
     *
     * @param \PhpParser\Node       $node  The node to parse
     * @param \ReckiCT\Parser\State $state The state to use for parsing
     *
     * @return \ReckiCT\Graph\Variable|null Returns a variable representing an
     *                                      expression, or null for statements.
     */
    public function parse(Node $node, State $state);

}
