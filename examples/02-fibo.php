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
 * @package Benchmarks
 */

/**
 * This example shows how you can get a performance benefit by compiling an
 * expensive PHP function.
 */

use ReckiCT\Jit;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * A Fibonacci generator!!!
 *
 * This is a recursive implementation. Very slow on PHP, and MUCH faster
 * when compiled.
 *
 * @param int $n The number of the sequence to generate
 *
 * @return int the fibonacci number!
 */
function fibo($n)
{
    if ($n < 2) {
        return 1;
    }

    return fibo($n - 1) + fibo($n - 2);
}

/**
 * Compile the function using the JITFU extension!
 */
$func = Jit::jitfu('fibo');

/**
 * Now let's benchmark the two implementations!
 *
 * If you don't have JitFu installed, then both should be equal.
 */
benchmark("fibo", "PHP");
benchmark($func, "ReckiCT");

/**
 * A very light weight benchmark tool, which runs the code 100,000 times.
 *
 * For very simple functions like the one we are testing here, we don't expect
 * a lot of gain (if any), since the overhead of the functional call will dominate
 * the runtime.
 *
 * @param callable $func  The function to benchmark
 * @param string   $label The label of the test, for output
 *
 * @return void
 */
function benchmark(callable $func, $label)
{
    $start = microtime(true);
    $result = $func(30);
    $end = microtime(true);
    printf("%s completed fibo(30)==$result in %01.4F seconds\n", $label, $end - $start);
}
