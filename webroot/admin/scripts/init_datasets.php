<?php

require "../../../resources/autoload.php";

$ldap = new unityLDAP(config::LDAP["uri"], config::LDAP["bind_dn"], config::LDAP["bind_pass"]);
$users = $SERVICE->ldap()->getAllUsers($SERVICE);  // get all users

//var_dump($users);

//$storage->createHomeDirectory("hsaplakoglu_umass_edu");
//$storage->deleteHomeDirectory("hsaplakoglu_umass_edu");

foreach ($users as $user) {
    $user->initHomeDirectory();
}