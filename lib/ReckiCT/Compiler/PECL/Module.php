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

    protected $constants = [];

    public function getConstant($value) {
        foreach ($this->constants as $constant) {
            if ($constant['value'] === $value) {
                return $constant['id'];
            }
        }
        $id = count($this->constants);
        $this->constants[] = [
            'value' => $value,
            'id' => $id
        ];
        return $id;
    }

    public static function getConstants() {
        return $this->map;
    }

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getGlobal($name) {
        return strtoupper($this->name) . "_G({$name})";
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
        $count = count($this->constants);
        $code = <<<EOF
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif
#include "php.h"
#include "php_{$this->name}.h"
#include <stdint.h>

ZEND_DECLARE_MODULE_GLOBALS({$this->name})

EOF;

        $code .= "static PHP_MINIT_FUNCTION({$this->name}) {\n";
        foreach ($this->constants as $constant) {
            $val_encoded = preg_replace_callback("([^a-zA-Z0-9])", function($x) {
                return '\x' . str_pad(dechex(ord($x[0])), 2, '0', STR_PAD_LEFT);
            }, $constant['value']);
            $val_len = strlen($constant['value']);
            $code .= "\t{$upperName}_G(string_constants)[{$constant['id']}] = recki_string_init(\"{$val_encoded}\", {$val_len}, 1);\n";
        }
        $code .= "}\n\n";

        $code .= "static PHP_MSHUTDOWN_FUNCTION({$this->name}) {\n";
        foreach ($this->constants as $constant) {
            $code .= "\trecki_string_release({$upperName}_G(string_constants)[{$constant['id']}]);\n";
        }
        $code .= "}\n\n";

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
    PHP_MINIT({$this->name}),
    PHP_MSHUTDOWN({$this->name}),
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
        $count = count($this->constants);
        return <<<EOF
#ifndef PHP_{$upperName}_H

#define PHP_{$upperName}_H 1
#define PHP_{$upperName}_VERSION "1.0"
#define PHP_{$upperName}_EXTNAME "{$this->name}"

#include <stdint.h>

#ifdef ZTS
#include "TSRM.h"
#endif

#if PHP_VERSION_ID >= 70000
#define reckistring zend_string
#define reckirefcounted zend_refcounted
#define recki_string_init zend_string_init
#define recki_string_copy zend_string_copy
#define recki_string_realloc zend_string_realloc
#else

#define RECKI_ENDIAN_LOHI_3(lo, mi, hi)    lo; mi; hi;

#define reckistring recki_string
#define reckirefcounted recki_refcounted

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
    assert(GC_REFCOUNT(s) > 0);
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

zend_always_inline recki_string *recki_string_realloc(recki_string *s, size_t len, int persistent) {
    recki_string *ret;
    if ((persistent ^ (GC_FLAGS(s) & IS_STR_PERSISTENT)) == 0 && EXPECTED(GC_REFCOUNT(s)==1)) {
        ret = (recki_string*) perealloc(s, ZEND_MM_ALIGNED_SIZE(_STR_HEADER_SIZE + len + 1), persistent);
        ret->len = len;
    } else {
        ret = recki_string_alloc(len, persistent);
        memcpy(ret->val, s->val, (len > s->len ? s->len : len) + 1);
        ret->val[len] = '\\0';
        GC_REFCOUNT(s)--;
    }
    return ret;
}

#endif

zend_always_inline reckistring *recki_string_concat(reckistring *s, reckistring *l, reckistring *r) {
    if (s == l) {
        s = recki_string_realloc(s, l->len + r->len, 0);
        memcpy(s->val + s->len - r->len, r->val, r->len);
    } else if (s != NULL) {
        s = recki_string_realloc(s, l->len + r->len, 0);
        memcpy(s->val, l->val, l->len);
        memcpy(s->val + l->len, r->val, r->len);
    } else {
        s = recki_string_copy(l);
        s = recki_string_realloc(s, l->len + r->len, 0);
        memcpy(s->val + l->len, r->val, r->len);
    }
    return s;
}

ZEND_BEGIN_MODULE_GLOBALS({$this->name})
    reckistring* string_constants[{$count}];
ZEND_END_MODULE_GLOBALS({$this->name})

#ifdef ZTS
#define {$upperName}_G(v) TSRM({$this->name}_globals_id, zend_{$this->name}_globals *, v)
#else
#define {$upperName}_G(v) ({$this->name}_globals.v)
#endif

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