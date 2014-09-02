<!--
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
-->

Introduction
============

Recki-CT is a PHP compiler toolkit **written in PHP itself**.

## What is this for?

A compiler toolkit is useful for [static analysis](http://en.wikipedia.org/wiki/Static_program_analysis) and creating high performance code.

## FAQ (Frequently Asked Questions

1. **Why Is PHP Code Not Optimized Already**

    PHP itself is a very dynamic language. The dynamic nature makes it a very forgiving and easy language to program in. But it also makes it difficult
    to make it extremely fast. 

    The original version of PHP (using the Zend Engine) uses a custom virtual machine, which executes PHP code. This turns out to be quite fast in the general
    sense of the word. But it can't compare to compiled languages that execute in native machine code. It's typically plenty fast for most usages.

    So other projects (such as HHVM and HippyVM) have tried to improve the performance by implementing a JIT (Just-In-Time) compiler. This basically works by compiling loops and frequently run code to native machine code at runtime. For loop heavy code, and code that's mostly static, this can have a significant 
    speed advantage. And benchmarks show that increases for average applications of 30% or more, and increases for certain benchmarks can be over 30 times faster.

    But it still isn't nearly as fast as natively compiled static code. Optimized C code can be 10's of times faster than even a JIT compiler is capable of.

    One reason is the cost of complex optimizations like loop unrolling, function inlining and dead code removal. These optimizations can be WAY too expensive to
    execute at runtime for a JIT compiler. But with an AOT (Ahead-of-Time) compiler, they can be pre-computed.

    This opens the door to a lot more aggressive optimizations. But it comes at a cost. The code that can be optimized must be sufficiently static to be reasoned
    about. This means that a number of dynamic language features cannot be supported.

2. **What Does "Compiled" Mean?**

    Compiled is a very overloaded term. 

    In a technical sense, it could mean any code that's translated into another form prior to executing. So normal PHP (with the Zend Engine) is compiled in this sense, since it uses an opcode representation as its compiler target. 

    Practically, we typically meant that compiled code is translated directly to machine code (code the processor understands directly), rather than run on top of other code.

    Recki-CT does both. It translates existing code (between 4 forms, see [Basic Operation](2_basic_operation.md) for more information). And the end result (if you have [JIT-Fu](https://github.com/krakjoe/jitfu) installed) is machine code.

3. **What's Different About Recki-CT?**

    There are two major differences between every other implementation of PHP and Recki-CT.

    1. Recki-CT does not attempt to re-implement the entire PHP language. It instead augments an existing implementation.

        That means that if you want to adopt Recki-CT in your project, it's not an all-or-nothing bet. You can add it where it makes sense, and let the existing
        implementation run on your current platform.

        This lets Recki-CT focus on areas where it can make a big improvement, and leave other areas up to the underlying platform. It's designed to work together
        with your chosen implementation, not replace it.

    2. Recki-CT is written in PHP. This is done because, well, why not? It means that anyone can help work on the system, not just those that know arbitrary
        language `$y`.

        It also means that one set of tools can be used to build, test and execute both the compiler, and its output.

4. **What Optimizations Does Recki-CT Provide?**

    It's still very early, and new optimizations are being added. But at present, here is an incomplete list:

    * *Redundant Assignment Elimination*

        This eliminates assignments (and variables) that would otherwise be pointless.

        For example:

            $a = 1;
            $b = $a;
            return $b;

        Would be optimized to

            $a = 1;
            return $a;

    * *Constant Propagation*

        This means that most places a constant is assigned to a variable, the variable will be replaced by the constant. 

        For example:

            $a = 1;
            return $a;

        Would become

            return 1;

        Note that some times this cannot happen, for example when a variable is going to be written to later (within a loop for example).

    * *Constant Expression Evaluation*

        This eliminates expressions that have a constant output.

        For example:

            $a = 1 + 2;
            return $a + 3;

        Would become:

            $a = 3;
            return $a + 3;

        But when both Constant Expression Evaluation and Constant Propagation is run, it would be reduced to:

            return 6;

    * *Unreachable Code Elimination*

        You can have code that appears after return blocks, or is otherwise unreachable (due to constant ifs, etc). This optimization will eliminate that code.

            return 1;
            $a = 3;

        Would become

            return 1;

    Other optimizations are actively being worked on. Here are some examples of potential future optimizations:

    * *Function Inlining*

        This would inline certain functions within another. So:

            function a($a) {
                $c = b($a) + $a;
                return $c;
            }

            function b($b) {
                return $b + 1;
            }

        When `a()` is compiled, could result in something like (prior to other optimizations):

            function a($a) {
                $c = $a + 1 + $a;
                return $c;
            }

        This can have a *huge* performance benefit due to avoiding the overhead of a function call.

    * *Constant Conditional Evaluation*

        This would convert static conditionals into non-conditionals (eliminating un-needed branches).

            if (true) {
                $a = 1;
            }

        Would become:

            $a = 1;

    etc...

5. **Why Don't You Implement Optimization Technique B?**

    Because we haven't implemented it yet. If you'd like to, feel free to contribute!

6. **What's With The Name?**

    The [Palaeoloxodon Recki](http://en.wikipedia.org/wiki/Palaeoloxodon_recki) is one of the largest species of elephants to have ever lived. This project's name therefore pays homage to the mascot of the PHP project, the elePHPant.

    The other part of the name, "CT" stands for "Compiler Toolkit".
    