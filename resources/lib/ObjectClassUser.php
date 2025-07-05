<?php

namespace UnityWebPortal\lib;

class ObjectClassUser extends ObjectClass
{
    protected static array $attributes_array = ["objectClass", "sshPublicKey"];
    protected static array $attributes_non_array = [
        "cn",
        "dn",
        "gecos",
        "gidNumber",
        "givenName",
        "homeDirectory",
        "loginShell",
        "mail",
        "o",
        "sn",
        "uid",
        "uidNumber"
    ];
}

// the following will be possible after an upgrade to php 8.4
// class PosixGroup extends \PHPOpenLDAPer\LDAPEntry
// {
//     public string $cn {
//         get => $this->getAttribute("cn")[0]
//     }
//     public string $gecos {
//         get => $this->getAttribute("gecos")[0]
//     }
//     public int $gidNumber {
//         get => $this->getAttribute("gidNumber")[0]
//     }
//     public string $givenName {
//         get => $this->getAttribute("givenName")[0]
//     }
//     public string $homeDirectory {
//         get => $this->getAttribute("homeDirectory")[0]
//     }
//     public string $loginShell {
//         get => $this->getAttribute("loginShell")[0]
//     }
//     public string $mail {
//         get => $this->getAttribute("mail")[0]
//     }
//     public string $o {
//         get => $this->getAttribute("o")[0]
//     }
//     public array $objectClass {
//         get => $this->getAttribute("objectClass")
//     }
//     public string $sn {
//         get => $this->getAttribute("sn")[0]
//     }
//     public array $sshPublicKey {
//         get => $this->getAttribute("sshPublicKey")
//     }
//     public string $uid {
//         get => $this->getAttribute("uid")[0]
//     }
//     public int $uidNumber {
//         get => $this->getAttribute("uidNumber")[0]
//     }
// }
// $LDAP->getUserEntry will also have to be updated to use LDAPConn::getEntryOfObjectClass
