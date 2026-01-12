#!/usr/bin/env php
<?php
include __DIR__ . "/init.php";
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;

$help = sprintf("Usage: %s group_name filename_of_users_to_remove\n", $argv[0]);
if (count(array_intersect($argv, ["-h", "--help"])) > 0) {
    _die($help, 0);
}
if (sizeof($argv) != 3) {
    _die($help, 1);
}

$gid = $argv[1];
$filename = $argv[2];
$group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$group->exists()) {
    _die("No such group '$gid'\n", 1);
}
($handle = fopen($filename, "r")) or _die("Can't open '$filename'\n", 1);
while (($line = fgets($handle)) !== false) {
    $uid = trim($line);
    $user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
    if (!$group->memberUIDExists($user->uid)) {
        print "Skipping '$uid' who doesn't appear to be in '$gid'\n";
        continue;
    }
    $group->removeUser($user);
}
fclose($handle);

