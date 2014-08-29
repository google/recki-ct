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

Intermediate Representation
===========================

Recki-CT uses a custom IR format that follows the form of a [Three Address Code](http://en.wikipedia.org/wiki/Three_address_code). This file specifies the IR format.

## Structure

The overall structure is based around function declarations. Function declarations are expressed as follows:

    function_declaration = "function" , space , identifier , space , type ;

    param_stmt = "param" , space , variable , space , type ;

    function = function_declaration , nl , { param_stmt , nl } , "begin" , nl , { instruction , nl } , "end" ;

    ir = { function_declaration } ;

A function declaration is started with the "function" keyword, followed by a space, and the identifier name (the name of the function) and its return type.

Then follows a series of parameter statements (which define a variable name to store the param in, and the type of the param).

Following the params is a `"begin"` statement. Then come the instructions, and ends with an `"end"`.

## Variables and Constants

    type = "long" | "double" | "numeric" | "string" | "unknown" | "void" ;

    variable = dollar , digit , { digit } ;

    value_long = [ "-" ] , digit, { digit } ;

    value_double = [ "-" ] , digit , { digit } , "." , digit , { digit } ;

    value_string = { letter | digit | "." | "/" | "=" }

    value = value_long | value_string | value_double ;

    label_value = "@" , digit , { digit } ;

## Operators

Operators are defined as the following:

    var_stmt = "var" , space , variable , space , type ; 

    const_stmt = "const" , space , variable , space , type , space , value ;

    return_stmt = "return" , space , variable ;

    label_stmt = "label" , space , label_value ;

    jump_stmt = "jump" , space , label_value ;

    jumpz_stmt = "jumpz" , space , variable , space , label_value ;

    func_call = "functioncall" , space , identifier { space , variable } , space variable ;

    recursion = "recursion" , { space , variable } , space variable ;

    stmt = var_stmt | return_stmt | const_stmt | label_stmt | jump_stmt | jumpz_stmt | func_call | recurse ;

    unary_operator = "!" | "~" | "assign" 

    unary_expression = unary_operator , space , variable , space , variable ;

    binary_operator = "+" | "-" | "*" | "/" | "&" | "|" | "^" | ">>" | "<<" | "==" | "!="
                        | "<" | "<=" | ">" | ">=" ;

    binary_expression = binary_operator , space, variable , space, variable , space, variable ;

    instruction = unary_expression | binary_expression | stmt ;

## Generic

    upper = "A" | "B" | "C" | "D" | "E" | "F" | "G"
           | "H" | "I" | "J" | "K" | "L" | "M" | "N"
           | "O" | "P" | "Q" | "R" | "S" | "T" | "U"
           | "V" | "W" | "X" | "Y" | "Z" ;

    lower = "a" | "b" | "c" | "d" | "e" | "f" | "g"
           | "h" | "i" | "j" | "k" | "l" | "m" | "n"
           | "o" | "p" | "q" | "r" | "s" | "t" | "u"
           | "v" | "w" | "x" | "y" | "z" ;

    letter = upper | lower ;

    digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;

    identifier_symbols = "\" | "_" ;

    identifier = ( letter | digit ) , { letter | digit | identifier_symbols } ;

    dollar = "$";

    space = ? US-ASCII character 32 ? ;

    nl = ? US-ASCII character 10 ? ;