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

        public static function provideOperatorTest() {
            $result = [
                ['add', [1, 3]],
                ['add', [1, 2]],
                ['add', [50, 50]],
            ];
            foreach ($result as $k => $v) {
                $result[$k][0] = "ReckiCT\IntegrationFunctions\\" . $v[0];
            }
            return $result;
        }

        public function testStrlen()
        {
            $cb = Jit::JitFu("ReckiCT\IntegrationFunctions\getStrlen");
            $string = "";
            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i, $cb($string), "For iteration $i");
                $string .= chr($i % 256);
            }
        }

        public function testCount()
        {
            $cb = Jit::JitFu("ReckiCT\IntegrationFunctions\getCount");
            $array = [];
            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i, $cb($array), "For iteration $i");
                $array[] = $i;
            }
        }

        /**
         * @dataProvider provideOperatorTest
         */
        public function testOperator($func, $args) {
            $cb = Jit::JitFu($func);
            $expected = call_user_func_array($func, $args);
            $actual = call_user_func_array($cb, $args);
            $this->assertEquals($expected, $actual);
        }

    }

}

namespace ReckiCT\IntegrationFunctions {

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
     * @param int $a
     * @param int $b
     * @return int The result
     */
    function add($a, $b) {
        return $a + $b;
    }

}
