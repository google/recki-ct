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

namespace JITFU;

class Func
{
    protected $compiled = false;
    protected $signature;
    protected $parent;
    protected $context;

    protected $unaryOps = array(
        'doNeg',
        'doToBool',
        'doToNotBool',
        'doAcos',
        'doAsin',
        'doAtan',
        'doAtan2',
        'doMin',
        'doMax',
        'doCeil',
        'doCos',
        'doCosh',
        'doExp',
        'doFloor',
        'doLog',
        'doLog10',
        'doRint',
        'doRound',
        'doSin',
        'doSinh',
        'doSqrt',
        'doTan',
        'doTanh',
        'doAbs',
        'doSign',
        'doIsNAN',
        'doIsFinite',
        'doIsInf',
        'doAlloca',
        'doLoad',
        'doLoadSmall',
        'doDup',
        'doAddressOf',
        'doCheckNull',
        'doReturn',
        'doPush',

    );

    protected $binaryOps = array(
        'doEq',
        'doNe',
        'doLt',
        'doLe',
        'doGt',
        'doGe',
        'doCmpl',
        'doCmpg',
        'doMul',
        'doMulOvf',
        'doAdd',
        'doAddOvf',
        'doSub',
        'doSubOvf',
        'doDiv',
        'doPow',
        'doRem',
        'doRemIEEE',
        'doAnd',
        'doOr',
        'doXor',
        'doShl',
        'doShr',
        'doUshr',
        'doSshr',
        'doStore',
        'doLoadElem',
        'doLoadElemAddress',
    );

    protected $trinaryOps = array(
        'doMemcpy',
        'doMemmove',
        'doMemset',
        'doStoreElem',
    );

    public function __call($name, array $values)
    {
        if (in_array($name, $this->binaryOps)) {
            $this->checkArgs(2, $values);
        } elseif (in_array($name, $this->unaryOps)) {
            $this->checkArgs(1, $values);
        } elseif (in_array($name, $this->trinaryOps)) {
            $this->checkArgs(3, $values);
        } else {
            throw new \BadMethodCallException("Unsupported method called");
        }

        return new Value($this, Type::of(Type::void));
    }

    private function checkArgs($num, array $values)
    {
        if (count($values) !== $num) {
            throw new \InvalidArgumentException("Binary operators support 2 operands");
        }
        foreach ($values as $value) {
            if (!$value instanceof Value) {
                throw new \InvalidArgumentException("Operands must be values");
            }
        }
    }

    public function __construct(Context $ctx, Signature $sig, \Closure $builder = null, Func $parent = null)
    {
        $this->context = $ctx;
        $this->signature = $sig;
        $this->parent = $parent;

        if ($builder) {
            $this->implement($builder);
        }
    }

    public function implement(\Closure $builder)
    {
        $parameters = array();
        $i = 0;
        while ($type = $this->signature->getParamType($i)) {
            $parameters[] = new Value($this, $type);
            $i++;
        }
        call_user_func($builder->bindTo($this), $parameters);
    }

    public function compile()
    {
        if ($this->compiled) {
            throw new \Exception("this function was already compiled");
        }
        $this->compiled = true;
    }

    public function isCompiled()
    {
        return $this->compiled;
    }

    public function isNested()
    {
        return $this->parent != null;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function __invoke()
    {
        throw new \RuntimeException("Not supported yet");
    }

    public function dump()
    {
        echo "Dumped";
    }

    public function reserveLabel()
    {
        return $this->doLabel();
    }

    public function doWhile(Value $value, callable $cb)
    {
        $cb();
    }

    public function doIf(Value $value, callable $if, callable $else = null)
    {
        $if();
        if ($else !== null) {
            $else();
        }
    }
    public function doIfNot(Value $value, callable $if, callable $else = null)
    {
        $if();
        if ($else !== null) {
            $else();
        }
    }

    public function doLabel(Label $label = null)
    {
        return is_null($label) ? new Label($this) : $label;
    }

    public function doBranch(Label $label = null)
    {
        return $this->doLabel($label);
    }

    public function doBranchIf(Value $cond, Label $label = null)
    {
        return $this->doLabel($label);
    }

    public function doBranchIfNot(Value $cond, Label $label = null)
    {
        return $this->doLabel($label);
    }

    public function doJumpTable(Value $cond, array $table)
    {
        foreach ($table as $callback) {
            $callback();
        }
    }

    public function doLoadRelative(Value $cond, $index)
    {
        return $value;
    }

    public function doPop($cond, $index = 1)
    {
        return $value;
    }

    public function doDeferPop($cond, $index = 1)
    {
        return $value;
    }

    public function doFlushDeferPop($cond, $index = 1)
    {
        return $value;
    }

    public function doConvert(Value $value, Type $type, $overflow = false)
    {
        return new Value($this, $type);
    }

    public function doReturnPtr(Value $value, Type $type)
    {
        return new Value($this, $type);
    }

    public function doPushPtr(Value $value, Type $type)
    {
        return new Value($this, $type);
    }

    public function doDefaultReturn()
    {
        return true;
    }

    public function doGetCallStack()
    {
        return new Value($this, Type::of(Type::void));
    }

    public function doCall(Func $toCall, array $params, $flags = 0)
    {
        foreach ($params as $param) {
            if (!$param instanceof Value) {
                throw new \InvalidArgumentException("Params must all be values");
            }
        }

        return new Value($this, Type::of(Type::void));
    }

}
