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
 * @package Main
 */

namespace ReckiCT;

use PhpParser\Lexer as AstLexer;
use PhpParser\Parser as AstParser;

use PhpParser\NodeTraverser as AstTraverser;

use PhpParser\NodeVisitor\NameResolver as AstNameResolver;

use PhpParser\Node\Stmt\Use_ as AstUse;
use PhpParser\Node\Stmt\Class_ as AstClass;
use PhpParser\Node\Stmt\ClassMethod as AstClassMethod;
use PhpParser\Node\Stmt\Function_ as AstFunction;
use PhpParser\Node\Stmt\Namespace_ as AstNamespace;

/**
 * This is a Facade over the compiler components
 *
 * For compilation to JITFU backends, use the JitFu static method
 *
 * $callable = \ReckiCT\Jit::JitFu($functionName);
 *
 * @api
 */
class Jit
{
    /**
     * @var \ReckiCT\Jit The current jit instance
     */
    protected static $instance;

    /**
     * This parser parses PHP code into an AST (Abstract Syntax Tree)
     *
     * @var \PhpParser\Parser The PhpParser parser
     */
    protected $astParser;

    /**
     * This resolver operates on the AST to resolve `use` and namespaces
     *
     * @var \PhpParser\NodeTraverser
     */
    protected $nameResolver;

    /**
     * This parser parses the AST into a CFG (Control Flow Graph)
     *
     * @var \ReckiCT\Parser\Parser The ReckiCT Parser
     */
    protected $parser;

    /**
     * This analyzer operates on both AST and CFG representations
     *
     * @var \ReckiCT\Analyzer\Analyzer The analyzer instance
     */
    protected $analyzer;

    /**
     * This generator converts a CFG into an intermediate representation
     *
     * @var \ReckiCT\Intermediary\Generator The IR generator
     */
    protected $generator;

    /**
     * This compiler converts the intermediate into JITFU instructions
     *
     * @var \ReckiCT\Compiler\JitFu\Compiler The JitFu compiler
     */
    protected $jitfucompiler;

    /**
     * This compiler converts the intermediate into PHP instructions
     *
     * @var \ReckiCT\Compiler\PHP\Compiler The PHP compiler
     */
    protected $phpcompiler;

    /**
     * array[] An array of AST arrays keyed on filename (cache)
     */
    protected $parsedFiles = [];

    /**
     * string[] An array of IR keyed on functionname (cache)
     */
    protected $parsedIr = [];

    /**
     * string[] An array of IR based on classname (cache)
     */
    protected $parsedClassIr = [];

    /**
     * Compile a function using the JITFU compiler
     *
     * @param string $name The function to compile
     *
     * @return callable The compiled function, or the name (which allows direct calling)
     */
    public static function JitFu($name)
    {
        return self::getInstance()->compileFunctionJitFu($name);
    }

    /**
     * Construct a Jit instance
     *
     * This is **not** a Singleton, but instead a short-cut to a single instance
     * It is not enforced, you can always create new instances of the compiler
     * manually.
     *
     * @return \ReckiCT\Jit The cached JIT instance
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Construct a new instance
     *
     * Note: this will construct a number of dependencies automatically
     */
    public function __construct()
    {
        $this->analyzer = Analyzer\Factory::analyzer();
        $this->astParser = new AstParser(new AstLexer());
        $this->nameResolver = new AstTraverser();
        $this->nameResolver->addVisitor(new AstNameResolver());
        $this->parser = Parser\Factory::parser();
        $this->generator = new Intermediary\Generator();
        if (class_exists('JITFU\Func')) {
            $this->jitfucompiler = new Compiler\JitFu\Compiler($this);
        }
        $this->phpcompiler = new Compiler\PHP\Compiler;
    }

    /**
     * Get the AST representation of a given function name, analyzed
     *
     * @param string $name The function name to find the AST for
     *
     * @return \PhpParser\Node\Stmt\Function_|null The function if found
     */
    public function getFunctionAst($name)
    {
        if (!function_exists($name)) {
            return;
        }
        $r = new \ReflectionFunction($name);
        $file = $this->parseFile($r->getFilename());
        $node = $this->findFunction($r->getName(), $file);
        if ($node) {
            $node = $this->analyzer->analyzeFunction($node);

            return $node;
        }
    }

    /**
     * Get the AST representation of a given class name, analyzed
     *
     * @param string $name The class name to find the AST for
     *
     * @return \PhpParser\Node\Stmt\Class_|null The function if found
     */
    public function getClassAst($name)
    {
        if (!class_exists($name)) {
            return;
        }
        $r = new \ReflectionClass($name);
        $file = $this->parseFile($r->getFilename());
        $node = $this->findClass($r->getName(), $file);
        if ($node) {
            $node = $this->analyzer->analyzeClass($node);
            return $node;
        }
    }

    /**
     * Get the graph representation of a given function name, analyzed
     *
     * @param string $name The function name to find the CFG for
     * @param PhpParser\Node\Stmt\Function_ $node The optional node to pass along
     *
     * @return \ReckiCT\Graph\Vertex\Function_|null The function if found
     */
    public function getFunctionGraph($name, AstFunction $node = null)
    {
        if (!$node) {
            $node = $this->getFunctionAst($name);
        }
        if ($node) {
            $graph = $this->parser->parseFunction($node);
            $this->analyzer->analyzeGraph($graph);

            return $graph;
        }
    }

    /**
     * Get the IR representation of a given function name, if found
     *
     * @param string $name The function name to find the IR for
     * @param PhpParser\Node\Stmt\Function_ $node The optional node to pass along
     *
     * @return string|false The function's IR if found, or false
     */
    public function getFunctionIR($name, AstFunction $node = null)
    {
        if (!isset($this->parsedIr[$name])) {
            $graph = $this->getFunctionGraph($name, $node);
            if ($graph) {
                $this->parsedIr[$name] = $this->generator->generateFunction($name, $graph);
            } else {
                $this->parsedIr[$name] = false;
            }
        }
        return $this->parsedIr[$name];
    }

    /**
     * Get the IR representation of a given class name, if found
     *
     * @param string $name The class name to find the IR for
     *
     * @return string|false The class's IR if found, or false
     */
    public function getClassIR($name)
    {
        if (!isset($this->parsedClassIr[$name])) {
            $ast = $this->getClassAst($name);
            $class = $this->parser->parseClass($ast);
            $this->parsedClassIr[$name] = $this->generator->generateClass($class);
        }
        return $this->parsedClassIr[$name];
    }

    public function getFileIr($filename) {
        $ast = $this->parseFile($filename);
        $ir = $this->getIrFromAstArray($ast);
        return trim($ir);
    }

    protected function getIrFromAstArray(array $ast) {
        $ir = '';
        foreach ($ast as $node) {
            switch (get_class($node)) {
                case AstNamespace::class:
                    $ir .= "\n" . $this->getIrFromAstArray($node->stmts);
                    break;
                case AstUse::class:
                    break;
                case AstFunction::class:
                    $ir .= "\n" . $this->getFunctionIR($node->namespacedName->toString(), $this->analyzer->analyzeFunction($node));
                    break;
                case AstClass::class:
                    $ir .= "\n" . $this->getClassIr($node->namespacedName->toString());
                    break;
                default:
                    throw new \RuntimeException("Unsupported statements found in file: " . get_class($node));
            }
        }
        return $ir;
    }

    /**
     * Get a callable for a given function name. This will compile the function
     * if possible.
     *
     * @param string $name The function name to compile
     *
     * @return callable The original function, or a compiled version if possible
     */
    public function compileFunctionJitFu($name)
    {
        if ($this->jitfucompiler) {
            $ir = $this->getFunctionIR($name);
            if ($ir) {
                return $this->jitfucompiler->compile($ir);
            }
        }

        return $name;
    }

    /**
     * Get a callable for a given function name. This will compile the function
     * if possible.
     *
     * @param string $name The function name to compile
     *
     * @return callable The original function, or a compiled version if possible
     */
    public function compileFunctionPHP($name)
    {
        if ($this->jitfucompiler) {
            $ir = $this->getFunctionIR($name);
            if ($ir) {
                return $this->phpcompiler->compile($ir);
            }
        }

        return $name;
    }

    /**
     * Get a callable for a given function ir. This will compile the function
     * if possible.
     *
     * @param string $ir   The intermediate representation to compile
     * @param string $name The fallback name to return if jitfu isn't loaded
     *
     * @return callable The original function, or a compiled version if possible
     */
    public function compileIrJitFu($ir, $name)
    {
        if ($this->jitfucompiler) {
            return $this->jitfucompiler->compile($ir);
        }

        return $name;
    }

    /**
     * Parse a given file into AST
     *
     * @param string $fileName the filename to parse
     *
     * @return \PhpParser\Node[] The parsed AST
     */
    public function parseFile($fileName)
    {
        $fileName = realpath($fileName);
        if (empty($this->parsedFiles[$fileName])) {
            $code = file_get_contents($fileName);
            $ast = $this->astParser->parse($code);
            $this->parsedFiles[$fileName] = $this->nameResolver->traverse($ast);
        }

        return $this->parsedFiles[$fileName];
    }

    /**
     * Given an AST array, find a named function's definition
     *
     * @param string            $name The function name to find
     * @param \PhpParser\Node[] $ast  The file's AST
     *
     * @return \PhpParser\Node\Stmt\Function_|null The found function's node or null
     */
    public function findFunction($name, array $ast)
    {
        foreach ($ast as $node) {
            if ($node instanceof AstNamespace) {
                $test = $this->findFunction($name, $node->stmts);
                if ($test) {
                    return $test;
                }
            } elseif ($node instanceof AstFunction) {
                if ($name === (string) $node->namespacedName) {
                    return $node;
                }
            }
        }
    }

    /**
     * Given an AST array, find a named class's definition
     *
     * @param string            $name The class name to find
     * @param \PhpParser\Node[] $ast  The file's AST
     *
     * @return \PhpParser\Node\Stmt\Class_|null The found class's node or null
     */
    public function findClass($name, array $ast)
    {
        foreach ($ast as $node) {
            if ($node instanceof AstNamespace) {
                $test = $this->findFunction($name, $node->stmts);
                if ($test) {
                    return $test;
                }
            } elseif ($node instanceof AstClass) {
                if ($name === (string) $node->namespacedName) {
                    return $node;
                }
            }
        }
    }
}
