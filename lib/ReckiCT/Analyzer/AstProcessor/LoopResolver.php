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
 * @package Analyzer
 * @subpackage AstProcessor
 */

namespace ReckiCT\Analyzer\AstProcessor;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

use PhpParser\Node\Expr\BooleanNot;

use PhpParser\Node\Scalar\LNumber;

use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\Label;

class LoopResolver extends NodeVisitorAbstract
{
    protected static $labelCounter = 0;
    protected $continueStack = array();
    protected $breakStack = array();

    /**
     * Called when entering a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node Node
     */
    public function enterNode(Node $node)
    {
        switch ($node->getType()) {
            case 'Stmt_Break':
                return $this->resolveStack($node, $this->breakStack);
            case 'Stmt_Continue':
                return $this->resolveStack($node, $this->continueStack);
            case 'Stmt_Switch':
                // we need to fake it!
                $lbl = $this->makeLabel();
                $this->breakStack[] = $lbl;
                $this->continueStack[] = $lbl;
                break;
            case 'Stmt_Do':
            case 'Stmt_While':
            case 'Stmt_For':
                $this->continueStack[] = $this->makeLabel();
                $this->breakStack[] = $this->makeLabel();
        }

        return null;
    }

    protected function resolveStack(Node $node, array $stack)
    {
        if (!$node->num) {
            return new Goto_(end($stack));
        }
        if ($node->num instanceof LNumber) {
            $num = $node->num->value - 1;
            if ($num >= count($stack)) {
                throw new \LogicException("Too high of a count for " . $node->getType() . "");
            }
            $loc = array_slice($stack, -1 * $num, 1);

            return new Goto_($loc[0]);
        }
        throw new \LogicException("Unimplemented node value type");
    }

    /**
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * false:     $node is removed from the parent array
     *  * array:     The return value is merged into the parent array (at the position of the $node)
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node|false|Node[] Node
     */
    public function leaveNode(Node $node)
    {
        switch ($node->getType()) {
            case 'Stmt_For':
                return $this->compileFor($node);
            case 'Stmt_While':
                return $this->compileWhile($node);
            case 'Stmt_Do':
                return $this->compileDo($node);
            case 'Stmt_Switch':
                // make break still work
                array_pop($this->continueStack);

                return [$node, new Label(array_pop($this->breakStack))];

        }

        return null;
    }

    protected function makeLabel()
    {
        return 'compiled_label_ReckiCT_' . self::$labelCounter++;
    }

    protected function compileFor(Node $node)
    {
        $return = [];
        foreach ($node->init as $subnode) {
            $return[] = $subnode;
        }
        $startName = $this->makeLabel();
        $continueName = array_pop($this->continueStack);
        $endName = array_pop($this->breakStack);

        $return[] = new Label($startName);

        for ($i = 0; $i < count($node->cond) - 1; $i++) {
            $return[] = $node->cond[$i];
        }

        if (isset($node->cond[$i])) {
            $cond = $node->cond[$i];
            if ($cond instanceof BooleanNot) {
                $cond = $cond->expr;
            } else {
                $cond = new BooleanNot($cond);
            }

            $return[] = new If_(
                $cond,
                [
                    'stmts' => [
                        new Goto_($endName)
                    ]
                ]
            );
        } else {
            // empty condition, no need for the if...
        }

        foreach ($node->stmts as $stmt) {
            $return[] = $stmt;
        }

        $return[] = new Label($continueName);

        foreach ($node->loop as $subnode) {
            $return[] = $subnode;
        }

        $return[] = new Goto_($startName);
        $return[] = new Label($endName);

        return $return;
    }

    protected function compileWhile(Node $node)
    {
        $return = [];

        $startName = array_pop($this->continueStack);
        $endName = array_pop($this->breakStack);

        $return[] = new Label($startName);

        $cond = $node->cond;
        if ($cond instanceof BooleanNot) {
            $cond = $cond->expr;
        } else {
            $cond = new BooleanNot($cond);
        }

        $return[] = new If_(
            $cond,
            [
                'stmts' => [
                    new Goto_($endName)
                ]
            ]
        );

        foreach ($node->stmts as $stmt) {
            $return[] = $stmt;
        }

        $return[] = new Goto_($startName);
        $return[] = new Label($endName);

        return $return;
    }

    protected function compileDo(Node $node)
    {
        $return = [];
        $startName = $this->makeLabel();

        $continueName = array_pop($this->continueStack);
        $endName = array_pop($this->breakStack);

        $return[] = new Label($startName);

        foreach ($node->stmts as $stmt) {
            $return[] = $stmt;
        }

        $return[] = new Label($continueName);
        $return[] = new If_(
            $node->cond,
            [
                'stmts' => [
                    new Goto_($startName)
                ]
            ]
        );

        $return[] = new Label($endName);

        return $return;
    }

}
