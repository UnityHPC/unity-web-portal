<?php

namespace UnityWebPortal\lib;

class ObjectClassGroup extends ObjectClass
{
    protected static array $attributes_array = ["objectClass", "memberUid"];
    protected static array $attributes_non_array = ["cn", "dn", "gidNumber"];
}

// the following will be possible after an upgrade to php 8.4
// class PosixGroup extends \PHPOpenLDAPer\LDAPEntry
// {
//     public string $cn {
//         get => $this->getAttribute("cn")[0]
//     }
//     public int $gidNumber {
//         get => $this->getAttribute("gidNumber")[0]
//     }
//     public array $memberUid {
//         get => $this->getAttribute("memberUid")
//     }
//     public array $objectClass {
//         get => $this->getAttribute("objectClass")
//     }
// }
// $LDAP->getUserGroupEntry,getOrgGroupEntry,getPIGroupEntry will also have to be
// updated to use LDAPConn::getEntryOfObjectClass
