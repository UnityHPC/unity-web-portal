<?php
if (sizeof($argv) == 1 or in_array($argv[1], array("-h", "--help"))) {
    die("Usage: $argv[0] group_name filename_of_users_to_remove\n");
}
require_once __DIR__ . "/../resources/autoload.php";
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;

$group = $argv[1];
$filename = $argv[2];
$parent_group = new UnityGroup($group, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
if (!$parent_group->exists()) {
    die("No such group");
}
$handle = fopen($filename, "r") or die("Can't open $filename");
while (($line = fgets($handle)) !== false) {
    $username = trim($line);
    $user = new UnityUser($username, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
    if (!$parent_group->userExists($user)) {
        print("Skipping $username who doesn't appear to be in $group\n");
        continue;
    }
    $parent_group->removeUser($user);
}
fclose($handle);
