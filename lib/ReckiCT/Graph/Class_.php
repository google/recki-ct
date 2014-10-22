<?php

namespace ReckiCT\Graph;

use ReckiCT\Graph\Vertex\Method;

class Class_ {
    
    protected $name;
    protected $extends;
    protected $implements = [];
    protected $methods = [];
    protected $properties = [];

    public function __construct($name, $extends, array $implements) {
        $this->name = $name;
        $this->extends = $extends;
        $this->implements = $implements;
    }

    public function getName() {
        return $this->name;
    }

    public function getExtends() {
        return $this->extends;
    }

    public function getImplements() {
        return $this->implements;
    }

    public function addMethod(Method $method) {
        $this->methods[] = $method;
    }

}