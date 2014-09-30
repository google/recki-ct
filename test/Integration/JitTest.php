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

namespace ReckiCT\Integration {
    use PhpParser\Parser;
    use PhpParser\Lexer;
    use ReckiCT\Jit;

    use PHPUnit_Framework_TestCase as TestCase;

    class JitTest extends TestCase
    {

        public function testStrlen()
        {
            $cb = Jit::JitFu("ReckiCT\Mocks\getStrlen");
            $string = "";
            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i, $cb($string), "For iteration $i");
                $string .= chr($i % 256);
            }
        }

        public function testCount()
        {
            $cb = Jit::JitFu("ReckiCT\Mocks\getCount");
            $array = [];
            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i, $cb($array), "For iteration $i");
                $array[] = $i;
            }
        }

        public function testFirst()
        {
            $cb = Jit::JitFu("ReckiCT\Mocks\first");
            $array = [1];
            for ($i = 0; $i < 10; $i++) {
                $this->assertEquals(1, $cb($array), "For iteration $i");
                $array[] = $i;
            }
        }

    }

}

namespace ReckiCT\Mocks {

    /**
     * @param  string $a
     * @return int    The length
     */
    function getStrlen($a)
    {
        return strlen($a);
    }

    /**
     * @param  int[]  $a
     * @return int    The length
     */
    function getCount($a)
    {
        return count($a);
    }

    /**
     * @param int[] $a
     * @return int  The first value
     */
    function first($a) {
        return $a[0];
    }

}
