<?php

namespace UnityWebPortal\lib;

use Exception;

/**
 * Uses magic __get to return $this->entry->getAttribute($x) or $this->entry->getAttribute($x)[0]
 * $attributes_array is a case sensitive list of LDAP attribute names that should be an array
 * $attributes_non_array is a case sensitive list of LDAP attribute names that should be a single
 * value instead of an array
 * $entry is a PHPOpenLDAPer\LDAPEntry
 * @since 8.3.0
 */
class ObjectClass
{
    private ?LDAPEntry $entry = null; // define in constructor of child class
    protected static array $attributes_array = [];
    protected static array $attributes_non_array = [];

    public function __get(string $property): mixed
    {
        assert(!is_null($this->entry));
        if (in_array($property, static::$attributes_array, true)) {
            return $this->entry->getAttribute($property);
        }
        if (in_array($property, static::$attributes_non_array, true)) {
            $attribute = $this->entry->getAttribute($property);
            if (empty($attribute)) {
                throw new AttributeNotFound($property);
            }
            return $attribute[0];
        }
        throw new Exception("Unknown property '$property'");
    }

    public function __isset(string $property): bool
    {
        assert(!is_null($this->entry));
        if (in_array($property, static::$attributes_array, true)
            || in_array($property, static::$attributes_non_array, true)
        ) {
            return (!empty($this->getAttribute($property)));
        }
        return false;
    }
}
