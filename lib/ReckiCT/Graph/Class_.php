<?php

namespace ReckiCT\Graph;

use ReckiCT\Graph\Vertex\Method;

class Class_ {

    const VISIBILITY_PUBLIC = 0;
    const VISIBILITY_PROTECTED = 1;
    const VISIBILITY_PRIVATE = 2;
    
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

    public function getMethods() {
        return $this->methods;
    }

    public function getProperties() {
        return $this->properties;
    }

    public function addMethod(Method $method) {
        $this->methods[] = $method;
    }

    public function addProperty(Property $property) {
        $this->properties[$property->getName()] = $property;
    }

}