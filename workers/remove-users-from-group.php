#!/usr/bin/env php
<?php
$_SERVER["HTTP_HOST"] = "worker"; // see deployment/overrides/worker

require_once __DIR__ . "/../resources/autoload.php";
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;

// builtin die() makes a return code of 0, we want nonzero
function _die($msg)
{
    print $msg;
    exit(1);
}

if (sizeof($argv) != 3 or in_array($argv, ["-h", "--help"])) {
    die("Usage: $argv[0] group_name filename_of_users_to_remove\n");
}

$gid = $argv[1];
$filename = $argv[2];
$group = new UnityGroup($gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$group->exists()) {
    _die("No such group '$gid'\n");
}
($handle = fopen($filename, "r")) or _die("Can't open '$filename'\n");
while (($line = fgets($handle)) !== false) {
    $uid = trim($line);
    $user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
    if (!$group->memberExists($user)) {
        print "Skipping '$uid' who doesn't appear to be in '$gid'\n";
        continue;
    }
    $group->removeUser($user);
}
fclose($handle);

