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

function process_user_attribute_key($x)
{
    if ($x == "givenname") {
        return "firstname";
    }
    if ($x == "sn") {
        return "lastname";
    }
    if ($x == "o") {
        return "org";
    }
    return $x;
}

function process_user_attribute_value($x)
{
    if (in_array(
        $x,
        [
                "gidnumber",
                "givenname",
                "homedirectory",
                "loginshell",
                "mail",
                "o",
                "sn",
                "uid",
                "uidnumber",
                "gecos",
            ]
    )
    ) {
        return $x[0];
    }
    return $x;
}

function process_group_attribute_value($x)
{
    if ($x == "gidnumber") {
        return $x[0];
    }
    return $x;
}

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
    echo "waiting for LDAP response (users)...\n";
    $users = $LDAP->search("objectClass=posixAccount", $CONFIG["ldap"]["basedn"]);
    echo "response received.\n";
    // phpcs:disable
    $user_CNs = array_map(function ($x){return $x->getAttribute("cn")[0];}, $users);
    // phpcs:enable
    sort($user_CNs);
    $REDIS->setCache("sorted_users", "", $user_CNs);
    foreach ($users as $user) {
        $cn = $user->getAttribute("cn")[0];
        foreach ($user->getAttributes() as $key => $val) {
            $REDIS->setCache($cn, process_user_attribute_key($key), process_user_attribute_value($val));
        }
    }

    $org_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["orggroup_ou"]);
    echo "waiting for LDAP response (org_groups)...\n";
    $org_groups = $LDAP->search("objectClass=posixGroup", $CONFIG["ldap"]["basedn"]);
    echo "response received.\n";
    // phpcs:disable
    $org_group_CNs = array_map(function($x){return $x->getAttribute("cn")[0];}, $org_groups);
    // phpcs:enable
    sort($org_group_CNs);
    $REDIS->setCache("sorted_orgs", "", $org_group_CNs);
    foreach ($org_groups as $org_group) {
        $REDIS->setCache($org_group->getAttribute("cn")[0], "members", $org_group->getAttribute("memberuid"));
    }

    $pi_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["pigroup_ou"]);
    echo "waiting for LDAP response (pi_groups)...\n";
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
        $REDIS->setCache($pi_group["cn"][0], $key, process_group_attribute_value($val));
    }
    foreach ($user_pi_group_member_of as $uid => $pi_groups) {
        // FIXME should be pi_groups
        $REDIS->setCache($uid, "groups", $pi_groups);
    }
    $REDIS->setCache("initializing", "", false);
    $REDIS->setCache("initialized", "", true);
    echo "done!\n";
}
