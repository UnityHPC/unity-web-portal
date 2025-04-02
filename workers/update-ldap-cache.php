<?php

require_once "../resources/autoload.php";

use UnityWebPortal\lib\{
    UnityConfig,
    UnityLDAP,
    UnityMailer,
    UnitySQL,
    UnitySite,
    UnitySSO,
    UnityUser,
    UnityRedis,
    UnityWebhook
};
use PHPOpenLDAPer\LDAPEntry;

$options = getopt("f");
if (array_key_exists("f", $options)) {
    echo "flushing cache...\n";
    $REDIS->flushAll();
}

if (!is_null($REDIS->getCache("initialized", ""))) {
    echo "cache is already initialized, nothing doing.\n";
} else {
    echo "rebuilding cache...\n";
    $user_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["user_ou"]);
    $users = $user_ou->getChildrenArray(true);
    $user_CNs = array_map(function($x){return $x["cn"][0];}, $users);
    sort($user_CNs);
    $REDIS->setCache("sorted_users", "", $user_CNs);
    foreach($users as $user){
        $attribute_array = UnityLDAP::parseUserChildrenArray($user);
        foreach($attribute_array as $key => $val){
            $REDIS->setCache($user["cn"][0], $key, $val);
        }
    }

    $org_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["orggroup_ou"]);
    $org_groups = $org_group_ou->getChildrenArray(true);
    $org_group_CNs = array_map(function($x){return $x["cn"][0];}, $org_groups);
    sort($org_group_CNs);
    $REDIS->setCache("sorted_orgs", "", $org_group_CNs);
    foreach($org_groups as $org_group){
        $REDIS->setCache($org_group["cn"][0], "members", $org_group["memberuid"]);
    }

    $pi_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["pigroup_ou"]);
    $pi_groups = $pi_group_ou->getChildrenArray(true);
    $pi_group_CNs = array_map(function($x){return $x["cn"][0];}, $pi_groups);
    sort($pi_group_CNs);
    $REDIS->setCache("sorted_pi_groups", "", $pi_group_CNs);
    foreach($pi_groups as $pi_group){
        $REDIS->setCache($pi_group["cn"][0], "members", $pi_group["memberuid"]);
        foreach($pi_group["memberuid"] as $member_uid){
            $REDIS->appendCacheArray($member_uid, "pi_groups", $pi_group["cn"][0]);
        }
    }
    $REDIS->setCache("initializing", "", false);
    $REDIS->setCache("initialized", "", true);
    echo "done!\n";
}

