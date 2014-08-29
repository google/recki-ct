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

/**
 * Represents variable types within the engine
 *
 * This class represents all type information for variables and associated
 * structures (constants, signatures, etc)
 *
 * @api
 */
class Type
{
    /**
     * Representing an unknown type
     *
     * This represents work-in-progress or un-derivable types during analysis
     *
     * @type int
     */
    const TYPE_UNKNOWN = -1;

    /**
     * Representing the explicit lack of a type
     *
     * @type int
     */
    const TYPE_VOID = 1;

    /**
     * Representing an integer (long)
     *
     * @type int
     */
    const TYPE_LONG = 2;

    /**
     * Representing a double (float)
     *
     * @type int
     */
    const TYPE_DOUBLE = 3;

    /**
     * Representing a string
     *
     * @type int
     */
    const TYPE_STRING = 4;

    /**
     * Representing a boolean
     *
     * In practice, this is a specifier for TYPE_LONG
     *
     * @type int
     */
    const TYPE_BOOLEAN = 5;

    /**
     * Representing a NULL type
     *
     * Practically, this is similar to void. However, it is slightly different in
     * that NULL always allocates a variable, where void never does.
     *
     * @type int
     */
    const TYPE_NULL = 6;

    /**
     * Representing a mixed type
     *
     * This is different from Unknown. Zval represents types that can and do change
     *
     * @type int
     */
    const TYPE_ZVAL = 7;

    /**
     * Representing a numeric type
     *
     * This is a generalizer for double and long. It is used for values that can
     * be represented by either double or long. This allows for later optimizations
     * to determine the best type to use. If it remains at the end of analysis
     * it should be treated as a long.
     *
     * @type int
     */
    const TYPE_NUMERIC = 8;

    /**
     * Representing a user-defined type (object)
     *
     * @type int
     */
    const TYPE_USER = 9;

    /**
     * Representing a array type
     *
     * These types **always** have sub-types
     *
     * @type int
     */
    const TYPE_ARRAY = 10;

    /**
     * Representing a hash table type
     *
     * These types **always** have sub-types
     *
     * @type int
     */
    const TYPE_HASH = 11;

    /**
     * @var int The current type being represented
     */
    protected $type = 0;

    /**
     * @var \ReckiCT\Type|null The subtype of the represented type
     */
    protected $subType;

    /**
     * @var string The specified user type (or empty if not a user type)
     */
    protected $userType = '';

    /**
     * Construct the type, using the supplied parameters
     *
     * @param int           $type     One of the TYPE_* constants indicating the wrapped type
     * @param \ReckiCT\Type $subType  The wrapped type (used for arrays)
     * @param string        $userType The user type (used for TYPE_USER)
     *
     * @throws \InvalidArgumentException if a $subType is supplied for non-array types
     * @throws \InvalidArgumentException if a $subType is not supplied for array types
     * @throws \InvalidArgumentException if a $userType is supplied for non-user types
     * @throws \InvalidArgumentException if a $userType is not supplied for user types
     */
    public function __construct($type, Type $subType = null, $userType = '')
    {
        $this->type = $type;
        if ($this->type == self::TYPE_ARRAY) {
            if ($subType) {
                $this->subType = $subType;
            } else {
                throw new \InvalidArgumentException("Complex type must have subtype specified");
            }
        } elseif ($subType) {
            throw new \InvalidArgumentException('SubTypes should only be provided to complex types');
        }
        if ($type === self::TYPE_USER) {
            if (empty($userType)) {
                throw new \InvalidArgumentException('User type must have a specifier');
            }
            $this->userType = $userType;
        } elseif ($userType) {
            throw new \InvalidArgumentException('User type information should only be provided to user types');
        }
    }

    /**
     * Generate a string representation of the type
     *
     * Note that this is reflexive with normalizeType
     *
     * @return string The string representation
     */
    public function __toString()
    {
        switch ($this->type) {
            case Type::TYPE_UNKNOWN:
                return 'unknown';
            case Type::TYPE_VOID:
                return 'void';
            case Type::TYPE_LONG:
                return 'long';
            case Type::TYPE_DOUBLE:
                return 'double';
            case Type::TYPE_NUMERIC:
                return 'numeric';
            case Type::TYPE_STRING:
                return 'string';
            case Type::TYPE_BOOLEAN:
                return 'bool';
            case Type::TYPE_NULL:
                return 'null';
            case Type::TYPE_ZVAL:
                return 'mixed';
            case Type::TYPE_ARRAY:
                return $this->subType . '[]';
            case Type::TYPE_USER:
                return $this->userType;
        }

        return '';
    }

    /**
     * Normalize a given type string into a new type instance
     *
     * Note that if the type can't be resolved, it will return TYPE_UNKNOWN
     *
     * Supported formats:
     *
     * "bool"    => TYPE_BOOLEAN
     * "boolean" => TYPE_BOOLEAN
     *
     * "int"     => TYPE_LONG
     * "integer" => TYPE_LONG
     * "long"    => TYPE_LONG
     *
     * "numeric" => TYPE_NUMERIC
     *
     * "double" => TYPE_DOUBLE
     * "float"  => TYPE_DOUBLE
     *
     * "string" => TYPE_STRING
     *
     * "void" => TYPE_VOID
     *
     * "null" => TYPE_NULL
     *
     * "mixed" => TYPE_ZVAL
     *
     * "array" => TYPE_ARRAY
     *
     * Additionally, formats can have `[]` appended to convert them to arrays-of-type
     *
     * For example: `int[]` will result in an array of integers
     *
     * @param string        $type    The type to normalize
     * @param \ReckiCT\Type $subType The optional subtype (for array types)
     *
     * @return \ReckiCT\Type The parsed type
     */
    public static function normalizeType($type, Type $subType = null)
    {
        if ($type instanceof Type) {
            return $type;
        }
        switch (strtolower($type)) {
            case 'bool':
            case 'boolean':
                return new Type(Type::TYPE_BOOLEAN);
            case 'int':
            case 'integer':
            case 'long':
                return new Type(Type::TYPE_LONG);
            case 'numeric':
                return new Type(Type::TYPE_NUMERIC);
            case 'float':
            case 'double':
                return new Type(Type::TYPE_DOUBLE);
            case 'string':
                return new Type(Type::TYPE_STRING);
            case 'void':
                return new Type(Type::TYPE_VOID);
            case 'null':
                return new Type(Type::TYPE_NULL);
            case 'mixed':
                return new Type(Type::TYPE_ZVAL);
            case 'array':
                if (!$subType) {
                    return new Type(Type::TYPE_HASH);
                }

                return new Type(Type::TYPE_ARRAY, $subType);
            case '':
                return new Type(Type::TYPE_UNKNOWN);
        }
        if (substr($type, -2) == '[]') {
            return new Type(Type::TYPE_ARRAY, static::normalizeType(substr($type, 0, -2)));
        }

        return new Type(Type::TYPE_USER, null, $type);
    }

    /**
     * Determine if a given type is equal to this type
     *
     * @param mixed $type The type to check, can be a string or Type
     *
     * @return boolean If the types are equal
     *
     * @throws \InvalidArgumentException If the supplied argument is invalid
     */
    public function equals($type)
    {
        if (is_string($type)) {
            if ($type == "array" && $this->type == Type::TYPE_ARRAY) {
                return true;
            }
            $type = self::normalizeType($type);
        } elseif (! $type instanceof Type) {
            throw new \InvalidArgumentException("Expecting a type, found $type");
        }
        if ($this->type == self::TYPE_ARRAY) {
            return $this->type == $type->type && $this->subType->equals($type->subType);
        }
        if ($this->type === self::TYPE_USER && $type->type === self::TYPE_USER) {
            return $this->userType === $type->userType;
        }

        return $this->type === $type->type;
    }

    /**
     * Get the associated subtype
     *
     * This replicates the $string[$offset] behavior that PHP uses with
     * non-array types
     *
     * @return Type the subtype associated with this type
     */
    public function getSubType()
    {
        if ($this->type == self::TYPE_ARRAY) {
            return $this->subType;
        } elseif ($this->type == self::TYPE_HASH) {
            return new Type(Type::TYPE_ZVAL);
        }

        return $this; // replicate $string[$offset] behavior...
    }

    /**
     * Get the user type associated with this type
     *
     * @return string The user-supplied type
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Return the type constant used to identify this type
     *
     * @return int The wrapped type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Determine if this type represents an unknown type
     *
     * @return boolean True if the type is unknown
     */
    public function isUnknown()
    {
        return $this->type === self::TYPE_UNKNOWN;
    }

    /**
     * Determine if this type represents a zval (mixed) type
     *
     * @return boolean True if the type is mixed
     */
    public function isZval()
    {
        return $this->type === self::TYPE_ZVAL;
    }

}
