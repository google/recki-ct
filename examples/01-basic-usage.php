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
 * This example shows how to compile a single function from PHP definition
 * to a natively compiled function.
 *
 * The source function is a normal PHP function!
 */

/**
 * This use satement is a helper to shorten the Jit call. It's completely
 * optional, and you could replace the calls down the line with ReckiCT\Jit::
 * if you wanted.
 */
use ReckiCT\Jit;

/**
 * We must include the composer autoloader, which also boostrap all of the
 * dependencies for us!!!
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * This is the function we're going to compile. It's simple for this example,
 * but more complex functions are supported.
 *
 * Note that the only thing that's required to compile the function is a docblock
 * which specifies the function's signature. Your code may already have this, and
 * if so, there's no need to change it!!!
 *
 * Also note that this is just PHP code. You can execute the original function,
 * and get the same behavior as the compiled version!
 *
 * @param int $n The parameter
 *
 * @return int The parameter, returned
 */
function test($n)
{
    return $n;
}

/**
 * Compile the function using the JITFU extension!
 *
 * Note that if the JITFU extension isn't installed, this will simply
 * return a "callable" string (the function name). So you can use it portably
 * across installations.
 */
$func = Jit::jitfu('test');

/**
 * Now let's benchmark the two implementations!
 *
 * If you don't have JitFu installed, then both should be equal.
 */
benchmark("test", "PHP");
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
    for ($i = 0; $i < 100000; $i++) {
        if ($i !== $func($i)) {
            throw new \Exception("This should never happen, result is invalid");
        }
    }
    $end = microtime(true);
    printf("%s completed in %01.4F seconds\n", $label, $end - $start);
}
