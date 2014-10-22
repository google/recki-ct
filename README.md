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

Recki-CT
========

[![Build Status](https://travis-ci.org/google/recki-ct.svg?branch=master)](https://travis-ci.org/google/recki-ct)
[![Coverage Status](https://img.shields.io/coveralls/google/recki-ct.svg)](https://coveralls.io/r/google/recki-ct?branch=master)

The **Recki Compiler Toolkit** for PHP

Disclaimer: This is not an official Google product.

Warning: This is an incomplete work-in-progress.

## Stability

Recki-CT is **pre-alpha** quality right now. This means that it shouldn't be used in production **at all**. 

## What Is Recki-CT?

Recki-CT is a set of tools that implement a compiler for PHP, and is written in PHP! 

Specifically, Recki-CT compiles a subset of PHP code. The subset is designed to allow a code base to be statically analyzed. This means that global variables, dynamic variables (variable-variables, variable function calls, etc) and references are not allowed. 

### What Isn't Recki-CT?

Recki-CT is not a re-implementation of PHP. It aims to be a limited subset of the language (one that can be staticly reasoned about).

This means that it is not designed to replace PHP, but instead augment it.

### Why?

PHP itself isn't slow. It's plenty fast enough for most use-cases. As a language, PHP has a lot of corner cases and results in a 
*really* complex engine implementation. As such, rewriting a new engine isn't going to gain you a lot. The complexity is going to 
be in there somewhere.

So with Recki-CT, we take a different approach. Rather than rewriting the entire engine, we sit on top of an existing engine. The compiler
then can compile PHP code into native machine code which can out-perform most JIT compiled implementations (sometimes by very significant margins).

The designed mode of operation for Recki is as an AOT (Ahead-Of-Time) compiler. Since it uses aggressive analysis and optimizations, 
runtime compilation would be a inefficient target. Instead, an Intermediate Representation can be cached, leaving only the final conversion
to machine code to happen at runtime. 

## Where can I find out more?

Check out the documentation!!!

 1. [Introduction and FAQ](doc/0_introduction.md)
 2. [Installation](doc/1_installation.md)
 3. [Basic Operation](doc/2_basic_operation.md)
 4. [Types](doc/3_types.md)
 5. [Intermediate Representation](doc/4_intermediate_representation.md)

## How do I install Recki-CT?

See the [Installation Documentation](doc/1_installation.md).

## How do I use Recki-CT?

A very simple example:

```php
/**
 * @return void
 */
function foo($bar) {}

// Instead of using:
foo($baz);

// Use:
$foo = Jit::JitFu('foo');
$foo($baz);
```

Note that a docblock *must* be present, and must document every parameter type and the return type.

Check out the `examples` folder for more examples!

## License

Recki-CT is released under the [Apache-2 License](LICENSE).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md)

And join the [Google Group](https://groups.google.com/forum/#!forum/recki-ct) mailing list.
