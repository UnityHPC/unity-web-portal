#!/bin/php
<?php

require_once __DIR__ . "/../resources/autoload.php";

use UnityWebPortal\lib\UnityConfig;
use UnityWebPortal\lib\UnityLDAP;
use UnityWebPortal\lib\UnityMailer;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\UnitySSO;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityRedis;
use UnityWebPortal\lib\UnityWebhook;
use PHPOpenLDAPer\LDAPEntry;

$options = getopt("fuh", ["help"]);
if (array_key_exists("h", $options) or array_key_exists("help", $options)) {
    echo "arguments:
    f: flush cache and then update
    u: update cache even if already initialized
    h --help: display this message\n";
    UnitySite::die();
}
if (array_key_exists("f", $options)) {
    echo "flushing cache...\n";
    $REDIS->flushAll();
}

if ((!is_null($REDIS->getCache("initialized", "")) and (!array_key_exists("u", $options)))) {
    echo "cache is already initialized, nothing doing.";
    echo " use -f argument to flush cache, or -u argument to update without flush.\n";
} else {
    echo "updating cache...\n";

    // search entire tree, some users created for admin purposes might not be in the normal OU
    echo "waiting for LDAP search (users)...\n";
    $users = $LDAP->search("objectClass=posixAccount", $CONFIG["ldap"]["basedn"]);
    echo "response received.\n";
    $user_CNs = $LDAP->getUserGroup()->getAttribute("memberuid");
    sort($user_CNs);
    $REDIS->setCache("sorted_users", "", $user_CNs);
    foreach ($users as $user) {
        $uid = $user->getAttribute("cn")[0];
        if (!in_array($uid, $user_CNs)) {
            continue;
        }
        $REDIS->setCache($uid, "firstname", $user->getAttribute("givenname")[0]);
        $REDIS->setCache($uid, "lastname", $user->getAttribute("sn")[0]);
        $REDIS->setCache($uid, "org", $user->getAttribute("o")[0]);
        $REDIS->setCache($uid, "mail", $user->getAttribute("mail")[0]);
        $REDIS->setCache($uid, "sshkeys", $user->getAttribute("sshpublickey"));
        $REDIS->setCache($uid, "loginshell", $user->getAttribute("loginshell")[0]);
        $REDIS->setCache($uid, "homedir", $user->getAttribute("homedirectory")[0]);
    }

    $org_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["orggroup_ou"]);
    echo "waiting for LDAP search (org groups)...\n";
    $org_groups = $org_group_ou->getChildrenArray(true);
    echo "response received.\n";
    // phpcs:disable
    $org_group_CNs = array_map(function($x){return $x["cn"][0];}, $org_groups);
    // phpcs:enable
    sort($org_group_CNs);
    $REDIS->setCache("sorted_orgs", "", $org_group_CNs);
    foreach ($org_groups as $org_group) {
        $gid = $org_group["cn"][0];
        $REDIS->setCache($gid, "members", (@$org_group["memberuid"] ?? []));
    }

    $pi_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["pigroup_ou"]);
    echo "waiting for LDAP search (pi groups)...\n";
    $pi_groups = $pi_group_ou->getChildrenArray(true);
    echo "response received.\n";
    // phpcs:disable
    $pi_group_CNs = array_map(function($x){return $x["cn"][0];}, $pi_groups);
    // phpcs:enable
    sort($pi_group_CNs);
    // FIXME should be sorted_pi_groups
    $REDIS->setCache("sorted_groups", "", $pi_group_CNs);

    $user_pi_group_member_of = [];
    foreach ($user_CNs as $uid) {
        $user_pi_group_member_of[$uid] = [];
    }
    foreach ($pi_groups as $pi_group) {
        $gid = $pi_group["cn"][0];
        $members = (@$pi_group["memberuid"] ?? []);
        foreach ($members as $uid) {
            if (in_array($uid, $user_CNs)) {
                array_push($user_pi_group_member_of[$uid], $gid);
            } else {
                echo "warning: group '$gid' has member '$uid' who is not in the users group!\n";
            }
        }
        $REDIS->setCache($gid, "members", (@$pi_group["memberuid"] ?? []));
    }
    foreach ($user_pi_group_member_of as $uid => $pi_groups) {
        // FIXME should be pi_groups
        $REDIS->setCache($uid, "groups", $pi_groups);
    }
    $REDIS->setCache("initializing", "", false);
    $REDIS->setCache("initialized", "", true);
    echo "done!\n";
}
