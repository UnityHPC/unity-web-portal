<?php

require_once "../resources/autoload.php";

// Get Users
$users = $LDAP->getAllUsers($SQL, $MAILER, $REDIS, $WEBHOOK, true);

$sorted_uids = array();

foreach ($users as $user) {
    $uid = $user->getUID();
    array_push($sorted_uids, $uid);

    $REDIS->setCache($uid, "firstname", $user->getFirstname(true));
    $REDIS->setCache($uid, "lastname", $user->getLastname(true));
    $REDIS->setCache($uid, "org", $user->getOrg(true));
    $REDIS->setCache($uid, "mail", $user->getMail(true));
    $REDIS->setCache($uid, "sshkeys", $user->getSSHKeys(true));
    $REDIS->setCache($uid, "loginshell", $user->getLoginShell(true));
    $REDIS->setCache($uid, "homedir", $user->getHomeDir(true));

    $parsed_groups = array();

    foreach ($user->getGroups(true) as $cur_group) {
        array_push($parsed_groups, $cur_group->getPIUID());
    }

    $REDIS->setCache($uid, "groups", $parsed_groups);
}

sort($sorted_uids);
$REDIS->setCache("sorted_users", "", $sorted_uids);

// Get groups
$groups = $LDAP->getAllPIGroups($SQL, $MAILER, $REDIS, $WEBHOOK, true);

$sorted_groups = array();

foreach ($groups as $group) {
    $gid = $group->getPIUID();
    array_push($sorted_groups, $gid);

    $parsed_members = array();
    foreach ($group->getMembers(true) as $member) {
        array_push($parsed_members, $member->getUID());
    }

    $REDIS->setCache($gid, "members", $parsed_members);
}

sort($sorted_groups);
$REDIS->setCache("sorted_groups", "", $sorted_groups);

// Get Orgs
$orgs = $LDAP->getAllOrgGroups($SQL, $MAILER, $REDIS, $WEBHOOK, true);

$sorted_orgs = array();

foreach ($orgs as $org) {
    $orgid = $org->getOrgID();
    array_push($sorted_orgs, $orgid);

    $parsed_orgs = array();
    foreach ($org->getMembers(true) as $member) {
        array_push($parsed_members, $member->getUID());
    }

    $REDIS->setCache($orgid, "members", $parsed_orgs);
}

sort($sorted_orgs);
$REDIS->setCache("sorted_orgs", "", $sorted_orgs);

// Confirmation Message
echo "OK\n";
