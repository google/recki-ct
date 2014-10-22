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
 * @package Compiler
 * @subpackage PECL
 */

namespace ReckiCT\Compiler\PECL;

use ReckiCT\Jit;

class Module
{

    protected $name;

    protected $functions = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function addFunction(Function_ $func) {
        $this->functions[] = $func;
    }

    public function compile() {
        return [
            $this->name . '.c' => $this->generateCFile(),
            'php_' . $this->name . '.h' => $this->generateHeaderFile(),
            'config.m4' => $this->generateConfigFile()
        ];
    }

    public function generateCFile() {
        $upperName = strtoupper($this->name);
        $code = <<<EOF
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif
#include "php.h"
#include "php_{$this->name}.h"
#include <stdint.h>

#if PHP_VERSION_ID >= 70000
#define reckistring zend_string
#define recki_string_init zend_string_init
#define recki_string_copy zend_string_copy
#else
#define reckistring recki_string


#define _STR_HEADER_SIZE XtOffsetOf(recki_string, val)

#define IS_STR_PERSISTENT 1<<0

#define GC_REFCOUNT(p)  ((recki_refcounted*)(p))->refcount
#define GC_TYPE(p)  ((recki_refcounted*)(p))->u.v.type
#define GC_FLAGS(p)  ((recki_refcounted*)(p))->u.v.flags
#define GC_INFO(p)  ((recki_refcounted*)(p))->u.v.gc_info
#define GC_TYPE_INFO(p)  ((recki_refcounted*)(p))->u.type_info

zend_always_inline void recki_string_release(recki_string *s) {
    if (--GC_REFCOUNT(s) == 0) {
        pefree(s, GC_FLAGS(s) & IS_STR_PERSISTENT);
    }
}

zend_always_inline recki_string *recki_string_copy(recki_string *s) {
    GC_REFCOUNT(s)++;
    return s;
}

zend_always_inline recki_string *recki_string_alloc(size_t len, int persistent) {
    recki_string *ret = (recki_string*) pemalloc(ZEND_MM_ALIGNED_SIZE(_STR_HEADER_SIZE + len + 1), persistent);
    GC_REFCOUNT(ret) = 1;
    GC_TYPE_INFO(ret) = IS_STRING | ((persistent ? IS_STR_PERSISTENT : 0) << 8);
    ret->h = 0;
    ret->len = len;
    return ret;
}

zend_always_inline recki_string *recki_string_init(const char *str, size_t len, int persistent) {
    recki_string *ret = recki_string_alloc(len, persistent);
    memcpy(ret->val, str, len);
    ret->val[len] = '\\0';
    return ret;
}

#endif

EOF;

        foreach ($this->functions as $func) {
            $code .= $func->getHeader();
        }
        $code .= "\n\n";
        
        foreach ($this->functions as $func) {
            $code .= $func->getCode();
        }
        $code .= "\n\n";

        foreach ($this->functions as $func) {
            // arginfo
            $code .= $func->getArgInfo();
        }
        $code .= "\n\n";

        $code .= "zend_function_entry {$this->name}_functions[] = {\n";
        foreach ($this->functions as $func) {
            $code .= $func->getFunctionEntry();
        }
        $code .= "\t{NULL, NULL, NULL}\n};\n\n\n";

        $code .= <<<EOF
zend_module_entry {$this->name}_module_entry = {
    STANDARD_MODULE_HEADER,
    PHP_{$upperName}_EXTNAME,
    {$this->name}_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_MINFO({$this->name}),
    PHP_{$upperName}_VERSION,
    STANDARD_MODULE_PROPERTIES
};


PHP_MINFO_FUNCTION({$this->name}) {
    php_info_print_table_start();
    php_info_print_table_row(2, "{$this->name} support", "Enabled");
    php_info_print_table_end();
};


#ifdef COMPILE_DL_{$upperName}
ZEND_GET_MODULE({$this->name})
#endif
EOF;

        return $code;
    }

    public function generateConfigFile() {
        $upperName = strtoupper($this->name);
        return <<<EOF
PHP_ARG_ENABLE({$this->name}, enable {$this->name} support, 
        [ --enable-{$this->name}       Enable {$this->name} support],yes)

if test "\$PHP_{$upperName}" != "no"; then
    AC_MSG_CHECKING([Checking for supported PHP versions])
    PHP_{$upperName}_FOUND_VERSION=`\${PHP_CONFIG} --version`
    PHP_{$upperName}_FOUND_VERNUM=`echo "\${PHP_{$upperName}_FOUND_VERSION}" | \$AWK 'BEGIN { FS = "."; } { printf "%d", ([\$]1 * 100 + [\$]2) * 100 + [\$]3;}'`
    if test "\$PHP_{$upperName}_FOUND_VERNUM" -lt "50500"; then
        AC_MSG_ERROR([not supported. Need a PHP version >= 5.5.0 (found \$PHP_{$upperName}_FOUND_VERSION)])
    else
        AC_MSG_RESULT([supported (\$PHP_{$upperName}_FOUND_VERSION)])
    fi
    AC_DEFINE(HAVE_{$upperName}, 1, [Compile with {$this->name} support])
    PHP_NEW_EXTENSION({$this->name}, {$this->name}.c, \$ext_shared)
fi
EOF;

    }

    public function generateHeaderFile() {
        $upperName = strtoupper($this->name);
        return <<<EOF
#ifndef PHP_{$upperName}_H

#define PHP_{$upperName}_H 1
#define PHP_{$upperName}_VERSION "1.0"
#define PHP_{$upperName}_EXTNAME "{$this->name}"


# define RECKI_ENDIAN_LOHI_3(lo, mi, hi)    lo; mi; hi;

#ifndef uint32_t
typedef unsigned int uint32_t;
#endif

#ifndef uint16_t
typedef unsigned short int uint16_t;
#endif

typedef struct _recki_refcounted {
    uint32_t    refcount;
    union {
        struct {
            RECKI_ENDIAN_LOHI_3(
                zend_uchar  type,
                zend_uchar  flags,
                uint16_t    gc_info)
        } v;
        uint32_t type_info;
    } u;
} recki_refcounted;

typedef struct _recki_string {
    recki_refcounted gc;
    zend_ulong       h;
    size_t           len;
    char             val[1];
} recki_string;

extern zend_module_entry {$this->name}_module_entry;

#define phpext_{$this->name}_ptr &{$this->name}_module_entry

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINFO_FUNCTION({$this->name});

#endif
EOF;
    }


}