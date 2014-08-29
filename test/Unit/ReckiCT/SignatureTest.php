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
 * @package Main
 */

namespace ReckiCT;

use PHPUnit_Framework_TestCase as TestCase;
use \Mockery as m;

/**
 * @coversDefaultClass \ReckiCT\Signature
 */
class SignatureTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getReturn
     * @covers ::getParams
     */
    public function testNoParams()
    {
        $type = m::mock(Type::class);
        $signature = new Signature($type, []);
        $this->assertSame($type, $signature->getReturn());
        $this->assertEquals([], $signature->getParams());
    }

    /**
     * @covers ::__construct
     * @covers ::getReturn
     * @covers ::getParams
     * @covers ::getParam
     * @uses ReckiCT\Type
     */
    public function testOneParam()
    {
        $ret = m::mock(Type::class);
        $param = m::mock(Type::class);
        $signature = new Signature($ret, [$param]);
        $this->assertSame($ret, $signature->getReturn());
        $this->assertSame([$param], $signature->getParams());
        $this->assertSame($param, $signature->getParam(0));
        $this->assertEquals(new Type(Type::TYPE_UNKNOWN), $signature->getParam(1));
    }

}
