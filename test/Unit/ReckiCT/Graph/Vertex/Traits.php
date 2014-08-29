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
 * @package Graph
 * @subpackage Vertex
 */

namespace ReckiCT\Graph\Vertex;

use ReckiCT\Graph\Variable;

trait Base
{
    protected function getClassName()
    {
        return substr(get_class($this), 0, -4); // remove Test
    }
}

trait BinaryBase
{
    /**
     * @covers ::getValue
     */
    public function testGetValue()
    {
        $class = $this->getClassName();
        $vertex = new $class($a = new Variable(), $b = new Variable());
        $this->assertSame($a, $vertex->getValue());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceA()
    {
        $class = $this->getClassName();
        $vertex = new $class($a = new Variable(), $b = new Variable());
        $vertex->replaceVariable($a, $new = new Variable());
        $this->assertSame([$new, $b], $vertex->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceDoesntAffectResult()
    {
        $class = $this->getClassName();
        $vertex = new $class($a = new Variable(), $b = new Variable());
        $vertex->replaceVariable($b, $new = new Variable());
        $this->assertSame([$a, $b], $vertex->getVariables());
    }

    /**
     * @covers ::replaceVariable
     */
    public function testReplaceNeither()
    {
        $class = $this->getClassName();
        $vertex = new $class($a = new Variable(), $b = new Variable());
        $vertex->replaceVariable(new Variable(), new Variable());
        $this->assertSame([$a, $b], $vertex->getVariables());
    }

}

trait AssignBase
{
    /**
     * @covers ::__construct
     * @covers ::getVariables
     * @covers ::getResult
     */
    public function testBasicConstruct()
    {
        $class = $this->getClassName();
        $vertex = new $class($a = new Variable(), $b = new Variable());
        $this->assertSame([$a, $b], $vertex->getVariables());
        $this->assertSame($b, $vertex->getResult());
    }

    /**
     * @covers ::isIdempotent
     */
    public function testIsIdempotent()
    {
        $class = $this->getClassName();
        $vertex = new $class(new Variable(), new Variable());
        $this->assertTrue($vertex->isIdempotent());
    }

    /**
     * @covers ::setResult
     */
    public function testSetResult()
    {
        $class = $this->getClassName();
        $vertex = new $class($a = new Variable(), new Variable());
        $vertex->setResult($new = new Variable());
        $this->assertSame([$a, $new], $vertex->getVariables());
        $this->assertSame($new, $vertex->getResult());
    }
}
