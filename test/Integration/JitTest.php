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
        public static function provideDynamicCode()
        {
            return array(
                array('int', array('int', 'int'), '+'),
                array('int', array('int', 'int'), '-'),
                array('int', array('int', 'int'), '*'),
                array('int', array('int', 'int'), '/'),
                array('int', array('int', 'int'), '%'),
                array('int', array('int', 'int'), '<<'),
                array('int', array('int', 'int'), '>>'),
                array('int', array('int', 'int'), '^'),
                array('int', array('int', 'int'), '&'),
                array('int', array('int', 'int'), '|'),
                array('float', array('float', 'float'), '+'),
                array('float', array('float', 'float'), '-'),
                array('float', array('float', 'float'), '*'),
                array('float', array('float', 'float'), '/'),
                array('float', array('float', 'float'), '%'),
                array('float', array('float', 'float'), '<<'),
                array('float', array('float', 'float'), '>>'),
                array('float', array('float', 'float'), '^'),
                array('float', array('float', 'float'), '&'),
                array('float', array('float', 'float'), '|'),
            );
        }

        public static function provideUnaryOp()
        {
            return array(
                array('ReckiCT\Mocks\fibo'),
                array('ReckiCT\Mocks\whileTest'),
            );
        }

        public static function provideBinaryOp()
        {
            return array(
                array('ReckiCT\Mocks\ifTest'),
                array('ReckiCT\Mocks\forTest'),
                array('ReckiCT\Mocks\castTest'),
            );
        }

        protected function setUp()
        {
            if (!extension_loaded('jitfu')) {
                $this->markTestSkipped('Integration tests require the JITFU extension');
            }
        }

        /**
         * @dataProvider provideDynamicCode
         */
        public function testDynamicOp($retType, $params, $op)
        {
            $code = 'return $p1 ' . $op . ' $p2;';
            $php = create_function('$p1, $p2', $code);
            $ast = (new Parser(new Lexer()))->parse('<?php
                /**
                 * @param ' . $params[0] . ' $p1
                 * @param ' . $params[1] . ' $p2
                 * @return ' . $retType . '
                 */
                function testDynamic($p1, $p2)
                {
                    ' . $code . '
                }');
            $jit = Jit::jit($ast[0]);
            switch ($params) {
                case array("int", "int"):
                    for ($i = -20; $i < 20; $i++) {
                        for ($j = -20; $j < 20; $j++) {
                            if ($j === 0 && in_array($op, array('%', '/'))) {
                                continue;
                            }
                            $this->assertEquals((int) $php($i, $j), $jit($i, $j));
                        }
                    }
                    break;
                case array("float", "float"):
                    for ($i = -20.0; $i < 20.0; $i++) {
                        for ($j = -20.0; $j < 20.0; $j++) {
                            if ($j === 0.0 && in_array($op, array('%', '/'))) {
                                continue;
                            }
                            $this->assertEquals((float) $php($i, $j), $jit($i, $j));
                        }
                    }
            }
        }

        /**
         * @dataProvider provideUnaryOp
         */
        public function testUnaryOp($op)
        {
            $cb = Jit::jit($op);
            for ($i = -10; $i < 10; $i++) {
                $this->assertEquals($op($i), $cb($i), "For $i");
            }
        }

        /**
         * @dataProvider provideBinaryOp
         */
        public function testBinaryOp($op)
        {
            $cb = Jit::jit($op);
            for ($i = -10; $i < 10; $i++) {
                for ($j = -10; $j < 10; $j++) {
                    $this->assertEquals($op($i, $j), $cb($i, $j), "For $i, $j", 0.001);
                }
            }
        }

        public function testBitapaluza()
        {
            $cb = Jit::jit('ReckiCT\Mocks\bitapaluza');
            for ($i = 0; $i < 17; $i++) {
                $this->assertEquals(\ReckiCT\Mocks\bitapaluza($i), $cb($i), "For $i");
            }
        }

        public function testStrlen()
        {
            $cb = Jit::jit("ReckiCT\Mocks\getStrlen");
            $string = "";
            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i, $cb($string));
                $string .= chr($i % 256);
            }
        }

        public function testGetAtOffset()
        {
            $array = range(0, 100);
            $cb = Jit::jit("ReckiCT\Mocks\getAtOffset");
            foreach ($array as $key => $value) {
                $this->assertEquals($value, $cb($array, $key));
            }
        }

        public function testAlterAtOffset()
        {
            $array = range(0, 100);
            $cb = Jit::jit("ReckiCT\Mocks\alterAtOffset");
            foreach ($array as $key => $value) {
                $this->assertEquals($value + 1, $cb($array, $key));
            }
        }

    }

}

namespace ReckiCT\Mocks {
    /**
     * @param  int $x
     * @return int
     */
    function whileTest($x)
    {
        $y = 0;
        while ($x-- > 0) {
            $y++;
        }

        return $y;
    }

    /**
     * @param  int $a
     * @param  int $b
     * @return int
     */
    function ifTest($a, $b)
    {
        if ($a) {
            return $a;
        }

        return $b;
    }

    /**
     * @param  int $a
     * @param  int $b
     * @return int
     */
    function forTest($a, $b)
    {
        for ($i = 0; $i < $a; $i++) {
            $b++;
        }

        return $b;
    }

    /**
     * @param  int   $a
     * @param  int   $b
     * @return float
     */
    function castTest($a, $b)
    {
        return (float) $a + $b;
    }

    /**
     * @param  int $x
     * @return int
     */
    function fibo($x)
    {
        if ($x <= 0) {
            return 0;
        }
        if ($x == 1) {
            return 1;
        }

        return fibo($x - 1) + fibo($x - 2);
    }

    /**
     * @param  int   $a
     * @param  int   $b
     * @return float
     */
    function halfPower($a, $b)
    {
        return pow($a, $b) / 2;
    }

    /**
     * @param  string $a
     * @return int    The length
     */
    function getStrlen($a)
    {
        return strlen($a);
    }

    /**
     * @param  int[] $a
     * @param  int   $key
     * @return int   The vaue of $a[$key]
     */
    function getAtOffset(array $a, $key)
    {
        return $a[$key];
    }

    /**
     * @param  int[] $a
     * @param  int   $key
     * @return int   The value of $a[$key] + 1
     */
    function alterAtOffset(array $a, $key)
    {
        $a[$key] = $a[$key] + 1;

        return $a[$key];
    }

    /**
     * @param  int $x
     * @return int
     */
    function bitapaluza($x)
    {
        $a = 1;
        $b = 1 << 63;
        $c = 0;
        $d = 0;
        $e = 0;
        $f = 0;
        for ($i = 0; $i < (1 << $x); $i++) {
            $a <<= 1;
            $b >>= 1;
            $c = $a | $b;
            $d = $a ^ $b;
            $e = $a & $b;
            $f = ~$a;
            $l = 1 << 8;
            while ($l >>= 1) {
                $c = $d << $e;
                $d = $e >> $f;
                $e = $f << $c;
                $f = $c >> $d;
            }
        }

        return $a + $b + $c + $d;
    }

}
