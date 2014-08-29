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
 */

namespace ReckiCT\Parser;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \ReckiCT\Parser\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @covers ::parser
     */
    public function testFactory()
    {
        $this->assertInstanceOf(Parser::class, Factory::parser());
    }

}
