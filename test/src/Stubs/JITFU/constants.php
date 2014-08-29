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
 * @package Stubs
 */

if (!extension_loaded("jitfu")) {
    define("JIT_TYPE_VOID",     1);
    define("JIT_TYPE_UINT",     2);
    define("JIT_TYPE_INT",      3);
    define("JIT_TYPE_ULONG",    4);
    define("JIT_TYPE_LONG",     5);
    define("JIT_TYPE_DOUBLE",   6);
    define("JIT_TYPE_STRING",   7);
    define("JIT_TYPE_VOID_PTR", 8);

    define("JIT_CONTEXT_STARTED",  1);
    define("JIT_CONTEXT_FINISHED", 2);
}
