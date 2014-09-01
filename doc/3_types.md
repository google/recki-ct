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

Types
=====

Recki-CT uses a series of system types to represent variables. These are represented by the `ReckiCT\Type` class.

## Unknown (`Type::TYPE_UNKNOWN`)

This is the default type. All new variables that aren't parameters are initialized using this type. It also will prevent compilation if present in output IR.

## Void (`Type::TYPE_VOID`)

A type that specifies the lack of a type. This is different from `Unknown` in that we know the type.

## Null (`Type::TYPE_NULL`):

A type that's similar to Void, except where Void never allocates a variable, Null will always allocate one.

## Numeric (`Type::TYPE_NUMERIC`)

This is a pseudo-type that "wraps" both `long` and `double` types. It represents any number that could be either an integer or a float. This allows delaying the decision on which exact type to use as late as possible.

### Long (`Type::TYPE_LONG`)

This type represents integer values. It is *always* 64 bit.

### Double (`Type::TYPE_DOUBLE`)

This type represents floating point values.

### Typing Rules

 * Addition, Subtraction and Multiplication (`+`, `-` and `*`):

    Longs are *always* generalized into numeric results (so `long + numeric === numeric` and `long + long === numeric`).

    Doubles are *always* specified into double results (so `long + double === double` and `numeric + double === double`).

 * Division (`/`):

    Division **always** results in a double

 * Bitwise Operations and Shifts (`|`, `&`, `^`, `<<`, `>>`):

    With few exceptions (string inputs, etc), Bitwise operations always result in Long typed results.

## String (`Type::TYPE_STRING`):

Represents a variable holding a string...

## Boolean (`Type::TYPE_BOOLEAN`):

Represents a variable holding a boolean. In practice, this will be compiled into a `long`, but for analysis reasons it's considered a separate type.

## Mixed (`Type::TYPE_ZVAL`):

Represents a variable of runtime-determined type. Support is presently limited.

## User (`Type::TYPE_USER`):

Represents the name of a class.

## Array (`Type::TYPE_ARRAY`):

Represents a numerically indexed array of something. Always requires a sub-type, which are the types of values.

Note that this is a literal array, not a PHP array. String keys are not supported, nor are sparse arrays.

## Hash (`Type::TYPE_HASH`):

Represents a PHP array. Values are always mixed. 

