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

$options = getopt("fu");
if (array_key_exists("f", $options)) {
    echo "flushing cache...\n";
    $REDIS->flushAll();
}

if ((!is_null($REDIS->getCache("initialized", "")) and (!array_key_exists("u", $options)))) {
    echo "cache is already initialized, nothing doing. use -f argument to flush cache, or -u argument to update without flush.\n";
} else {
    echo "updating cache...\n";
    $user_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["user_ou"]);
    echo "waiting for LDAP response (users)...\n";
    $users = $user_ou->getChildrenArray(true);
    echo "response received.\n";
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
    echo "waiting for LDAP response (org_groups)...\n";
    $org_groups = $org_group_ou->getChildrenArray(true);
    echo "response received.\n";
    $org_group_CNs = array_map(function($x){return $x["cn"][0];}, $org_groups);
    sort($org_group_CNs);
    $REDIS->setCache("sorted_orgs", "", $org_group_CNs);
    foreach($org_groups as $org_group){
        $REDIS->setCache($org_group["cn"][0], "members", $org_group["memberuid"]);
    }

    $pi_group_ou = new LDAPEntry($LDAP->getConn(), $CONFIG["ldap"]["pigroup_ou"]);
    echo "waiting for LDAP response (pi_groups)...\n";
    $pi_groups = $pi_group_ou->getChildrenArray(true);
    echo "response received.\n";
    $pi_group_CNs = array_map(function($x){return $x["cn"][0];}, $pi_groups);
    sort($pi_group_CNs);
    $REDIS->setCache("sorted_pi_groups", "", $pi_group_CNs);
    $user_pi_group_member_of = [];
    foreach($user_CNs as $uid){
        $user_pi_group_member_of[$uid] = [];
    }
    foreach($pi_groups as $pi_group){
        if (array_key_exists("memberuid", $pi_group)){
            $REDIS->setCache($pi_group["cn"][0], "members", $pi_group["memberuid"]);
            foreach($pi_group["memberuid"] as $member_uid){
                array_push($user_pi_group_member_of[$member_uid], $pi_group["cn"][0]);
            }
        } else {
            $REDIS->setCache($pi_group["cn"][0], "members", []);
        }
    }
    foreach($user_pi_group_member_of as $uid => $pi_groups){
        $REDIS->setCache($uid, "pi_groups", $pi_groups);
    }
    $REDIS->setCache("initializing", "", false);
    $REDIS->setCache("initialized", "", true);
    echo "done!\n";
}

