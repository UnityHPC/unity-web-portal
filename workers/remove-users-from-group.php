#!/usr/bin/env php
<?php
include __DIR__ . "/init.php";
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;

if (sizeof($argv) != 3 or in_array($argv, ["-h", "--help"])) {
    _die("Usage: {$argv[0]} group_name filename_of_users_to_remove\n", 1);
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

