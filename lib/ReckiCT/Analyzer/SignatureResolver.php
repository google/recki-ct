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
 */

namespace ReckiCT\Analyzer;

use ReckiCT\Signature;
use ReckiCT\Type;

use phpDocumentor\Reflection\DocBlock;

use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\ClassMethod;

use ReflectionFunction;

class SignatureResolver
{
    protected $internalMap = array(
        "count" => array("int", array("array")),
        "pow" => array("float", array("numeric", "numeric")),
        "strlen" => array("int", array("string")),
    );

    public function resolve($functionName)
    {
        if ($functionName instanceof Function_ || $functionName instanceof ClassMethod) {
            return $this->resolveSignature((string) $functionName->getDocComment());
        } elseif (isset($this->internalMap[$functionName])) {
            $params = [];
            foreach ($this->internalMap[$functionName][1] as $param) {
                $params[] = Type::normalizeType($param);
            }

            return new Signature(
                Type::normalizeType($this->internalMap[$functionName][0]),
                $params
            );
        } elseif (function_exists($functionName)) {
            $r = new ReflectionFunction($functionName);
            if (!$r->isInternal()) {
                return $this->resolveSignature($r->getDocComment());
            }
        }

        return new Signature(new Type(Type::TYPE_UNKNOWN), []);
    }

    public function resolveSignature($comment)
    {
        $docblock = new DocBlock((string) $comment);
        $return = $docblock->getTagsByName("return");
        if (count($return) !== 1) {
            $returnType = new Type(Type::TYPE_UNKNOWN);
        } else {
            $returnType = Type::normalizeType($return[0]->getType());
        }

        $params = $docblock->getTagsByName("param");
        $paramTypes = [];
        foreach ($params as $key => $param) {
            $paramTypes[] = Type::normalizeType($params[$key]->getType());
        }

        return new Signature($returnType, $paramTypes);
    }

    public function resolveVar($comment) {
        if (!$comment) {
            return new Type(Type::TYPE_UNKNOWN);
        }
        $docblock = new DocBlock((string) $comment);
        $var = $docblock->getTagsByName("var");
        if (count($var) !== 1) {
            return new Type(Type::TYPE_UNKNOWN);
        }
        return Type::normalizeType($var[0]->getType());
    }

}
