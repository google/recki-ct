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

Phi Resolving
=============

To understand how we resolve Phi (Φ) functions, we must first discuss why we are using phi functions in the first place.

## The Role Of Phi

When we convert code to SSA (Static Single Assignment) form, we want to ensure that every variable is assigned to **exactly** once. Once in SSA form, we can start to make certain assumptions and optimizations. 

Converting to SSA is rather easy for trivial code (without branches). Consider the following:

    $x = 1;
    $x = $x + 1;
    return $x;

To convert to SSA, we can start by numbering each `$x` variable, starting at `1`. Every usage of the variable gets the current counter, and every assignment increments the counter. So our prior code becomes:

    $x1 = 1;
    $x2 = $x1 + 1;
    return $x2;

This is simple so far. But what happens if we have an if statement:

    $x = 1;
    if ($a) {
        $x = 2;
    }
    return $x;

When we apply our previous rules for converting to SSA, we run into an issue:

    $x1 = 1;
    if ($a) {
        $x2 = 2;
    }
    return $x?;

Which `$x` are we supposed to return? In the case `$a` is false, we would choose `$x1`, but in the case it's true `$x2`... 

To solve this, we introduce the concept of a phi function:

    $x1 = 1;
    if ($a) {
        $x2 = 2;
    }
    $x3 = phi($x1, $x2);
    return $x3;

One way of looking at Phi, is that it "chooses" the correct value to use based on how it arrived. In other words, it knows the flow the function took to get there, it knows which version of the variable to choose.

Inserting the `phi` function is easy. Resolving (removing) is more difficult. So let's talk about that.

## Typing Of Phi Functions

Since phi functions "choose" the value at runtime, the type of the result will depend on the arguments.

If the arguments are all the same, then the result is the same as the arguments.

    int phi(int, int)

But what happens if the types aren't the same?

There are 3 cases where the types don't match:

1. Types Are Subsets (Generalizations) Of Each Other

    An integer and a floating point number are specifications of a general numeric type (see [The Chapter On Types](3_types.md)). In this case, the result is the *most specific* of the types:

        double phi(numeric, double)

    Or:

        int phi(numeric, int)

2. Types Are Different

    A string and an integer are fundamentally different types (as are double and integer). Therefore, they require different underlying storage.

    So the result type is mixed:

        mixed phi(string, int)

3. Types Are Unknown

    When one or more of the types are unknown, the result *must* be unknown

        unknown phi(int, unknown)

## Resolving Known Type Phi Functions

If a phi function has a known type (that's not mixed), then we can immediately resolve it. The way we resolve it, is simply by replacing each access/assignment of the arguments of the phi function with the result of it.

Let's see an example:

    $x1 = 1;
    if ($a) {
        $x2 = 2;
    }
    $x3 = phi($x1, $x2);
    return $x3;

If the types match (it doesn't matter what they are, just that they match), resolving this phi function will result in:

    $x3 = 1;
    if ($a) {
        $x3 = 2;
    }
    return $x3;

Note that this is functionally identical to our original code! That's the point.

So if the types match, the phi function let's us simply resolve them to the same variable.

### Note

If the arguments are subsets of each other, all variables will be changed to the more specific type. This is actually an optimization that reduces type changes.

    $x1 = 1;
    if ($a) {
        $x2 = 2.5;
    }
    $x3 = phi($x1, $x2);
    return $x3;

In this case, `$x1` will be typed as `numeric`, because it can be **exactly** represented as either an integer or a double. `$x2` can only be typed as `double`. So following our typing rules, `$x3` will be typed as the most specific type, and therefore be `double`.

So when we resolve the phi function, the code will become:

    $x3 = 1.0;
    if ($a) {
        $x2 = 2.5;
    }
    return $x3;

This avoids casts, while still allowing for types to be represented correctly.

## Resolving Unknown Types

In general, it's impossible to resolve an unknown type, because we don't know how to resolve it. Note that an unknown type is different from a mixed type (where we know that it could be one of many values). In practice, the presence of an unknown type should be rare, and only really happen if using unsupported operands or due to a compiler bug.

### Phi Interdepencies

However, this is one special case of unknown type that we must consider. Imagine the following code (which happens quite often with nested loops):

    $x2 = phi($x1, $x6)
    //...//
    $x6 = phi($x3, $x2)

We have 2 phi functions which are interdependent. Each relies on the result of the other for part of its type information. This means that by themselves, `$x2` and `$x6` will always be unknown type, since they depend on each other for typing information.

We could try brute-forcing the unknowns to see what would result in a valid type equation, but that's both error prone and slow.

Instead, let's create a graph.

The graph uses variables as vertices, and Phi-assignment as edges.

![Resolve Phi](resources/resolve_phi.png)

From this graph, we can see that the **type** relationship can be only impacted by `$x1` and `$x3`.

So by using the graph, we can find the cycles (interdependencies), and then find the inbound nodes to those variables. Then we can resolve the types!

So in this case, we use the normal type resolving rules (if they're equal, or if they are generalizations of each other) to type the remaining variables. 

Once we do that, we are able to treat both phi functions as normal typed (and hence revisit them for resolving).

## Resolving Different Types

If the types are incompatible, resolving them becomes MUCH harder. This isn't implemented yet, but here are a few approaches that I'm considering:

1. Duplicating the code.

    Since each branch has a different type, we'd need to split the remainder of the code into 2 copies, one where it's the first type, and one where it's the second type.

    For example:

        $x = 1;
        if ($a) {
            $x = "foo";
        }
        return $x;

    Would be compiled to:

        $x1 = 1;
        if ($a) {
            $x2 = "foo";
            return $x2;
        }
        return $x1;

    For the trivial case here, that's the obvious course to do. But with complex functions, this can get extremely expensive. It also can result in a combinatorial explosion of operations which would make this technique unrealistic for complex code.

2. Using A Variant

    Since the result is mixed, we could replace each variable with a variant (zval) which stores the current type information in a single struct with the value in a union. This is actually how PHP internally represents values. 

    The problem with this, is that it requires calling functions to do any operation. So adding two numbers goes from a single CPU operation to a full blown function call. It also requires promoting other interacting variables to variants as well.

    This will kill the majority of the optimizations that we can gain, so it should be used lightly.
