<?php

/**
 * PHP script to create org groups and org attribute to migrate old LDAP schema (v 1.0.0-RC1)
 */

require_once "../../../resources/autoload.php";

use UnityWebPortal\lib\UnityOrg;

$users = $LDAP->getAllUsers($SQL, $MAILER);

foreach ($users as $user) {
    $uid = $user->getUID();
    $parts = explode("_", $uid);
    $part_size = count($parts);
    $index_from_end = 2;

    $org = "";
    for ($i = $part_size - $index_from_end; $i < $part_size; $i++) {
        $org .= $parts[$i];
        if ($i < $part_size - 1) {
            $org .= "_";
        }
    }

    echo "<p>Found org <strong>$org</strong> for user <strong>$uid</strong></p>";

    // set ldap org
    $user->setOrg($org);

    $org_group = new UnityOrg($org, $LDAP, $SQL, $MAILER);
    if (!$org_group->exists()) {
        $org_group->init();
    }

    if (!$org_group->inOrg($user->getUID())) {
        $org_group->addUser($user);
    }
}
