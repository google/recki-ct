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
 * @package Graph
 */

namespace ReckiCT\Graph;

use ReckiCT\Type;

class Property extends Variable
{

    protected $visibility;
    protected $name;
    protected $static = false;
    protected $default = null;

    public function __construct($name, Type $type = null, $visibility = Class_::VISIBILITY_PUBLIC, $static = false, Constant $default = null)
    {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->static = $static;
        $this->default = $default;
        parent::__construct($type);
    }

    public function getName() {
        return $this->name;
    }

    public function getDefault() {
        return $this->default;
    }

    public function getVisibility() {
        return $this->visibility;
    }

    public function isStatic() {
        return $this->static;
    }

    public function __toString()
    {
        $visibility = '';
        switch ($this->visibility) {
            case Class_::VISIBILITY_PROTECTED:
                $visibility = "protected";
                break;
            case Class_::VISIBILITY_PRIVATE:
                $visibility = "private";
                break;
            case Class_::VISIBILITY_PUBLIC:
            default:
                $visibility = "public";
        }
        if ($this->static) {
            $visibility .= ' static';
        }
        return $visibility . ' ' . $this->type . ' ' . $this->name;
    }

}
