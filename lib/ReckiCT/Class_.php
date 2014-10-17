<?php

namespace ReckiCT;

class Class_ {
    
    protected $name;
    protected $extends;
    protected $implements = [];

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

}