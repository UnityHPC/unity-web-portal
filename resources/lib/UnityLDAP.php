<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPConn;
use PHPOpenLDAPer\LDAPEntry;

/**
 * An LDAP connection class which extends ldapConn tailored for the Unity Cluster
 */
class UnityLDAP extends ldapConn
{
    private const RDN = "cn"; // The defauls RDN for LDAP entries is set to "common name"

    public const POSIX_ACCOUNT_CLASS = [
        "inetorgperson",
        "posixAccount",
        "top",
        "ldapPublicKey",
    ];

    public const POSIX_GROUP_CLASS = ["posixGroup", "top"];

    private $custom_mappings_path =
        __DIR__ . "/../../" . CONFIG["ldap"]["custom_user_mappings_dir"];
    private $def_user_shell = CONFIG["ldap"]["def_user_shell"];
    private $offset_UIDGID = CONFIG["ldap"]["offset_UIDGID"];
    private $offset_PIGID = CONFIG["ldap"]["offset_PIGID"];
    private $offset_ORGGID = CONFIG["ldap"]["offset_ORGGID"];

    // Instance vars for various ldapEntry objects
    private $baseOU;
    private $userOU;
    private $groupOU;
    private $pi_groupOU;
    private $org_groupOU;
    private $adminGroup;
    private $userGroup;

    public function __construct()
    {
        parent::__construct(
            CONFIG["ldap"]["uri"],
            CONFIG["ldap"]["user"],
            CONFIG["ldap"]["pass"],
        );
        $this->baseOU = $this->getEntry(CONFIG["ldap"]["basedn"]);
        $this->userOU = $this->getEntry(CONFIG["ldap"]["user_ou"]);
        $this->groupOU = $this->getEntry(CONFIG["ldap"]["group_ou"]);
        $this->pi_groupOU = $this->getEntry(CONFIG["ldap"]["pigroup_ou"]);
        $this->org_groupOU = $this->getEntry(CONFIG["ldap"]["orggroup_ou"]);
        $this->adminGroup = $this->getEntry(CONFIG["ldap"]["admin_group"]);
        $this->userGroup = $this->getEntry(CONFIG["ldap"]["user_group"]);
    }

    public function getUserOU()
    {
        return $this->userOU;
    }

    public function getGroupOU()
    {
        return $this->groupOU;
    }

    public function getPIGroupOU()
    {
        return $this->pi_groupOU;
    }

    public function getOrgGroupOU()
    {
        return $this->org_groupOU;
    }

    public function getAdminGroup()
    {
        return $this->adminGroup;
    }

    public function getUserGroup()
    {
        return $this->userGroup;
    }

    public function getDefUserShell()
    {
        return $this->def_user_shell;
    }

    public function getNextUIDGIDNumber($uid)
    {
        $IDNumsInUse = array_merge(
            $this->getAllUIDNumbersInUse(),
            $this->getAllGIDNumbersInUse(),
        );
        $customIDMappings = $this->getCustomIDMappings();
        $customMappedID = $customIDMappings[$uid] ?? null;
        if (
            !is_null($customMappedID) &&
            !in_array($customMappedID, $IDNumsInUse)
        ) {
            return $customMappedID;
        }
        if (
            !is_null($customMappedID) &&
            in_array($customMappedID, $IDNumsInUse)
        ) {
            UnityHTTPD::errorLog(
                "warning",
                "user '$uid' has a custom mapped IDNumber $customMappedID but it's already in use!",
            );
        }
        return $this->getNextIDNumber(
            $this->offset_UIDGID,
            array_merge(
                $IDNumsInUse,
                array_values($this->getCustomIDMappings()),
            ),
        );
    }

    public function getNextPIGIDNumber()
    {
        return $this->getNextIDNumber(
            $this->offset_PIGID,
            array_merge(
                $this->getAllGIDNumbersInUse(),
                array_values($this->getCustomIDMappings()),
            ),
        );
    }

    public function getNextOrgGIDNumber()
    {
        return $this->getNextIDNumber(
            $this->offset_ORGGID,
            array_merge(
                $this->getAllGIDNumbersInUse(),
                array_values($this->getCustomIDMappings()),
            ),
        );
    }

    private function isIDNumberForbidden($id)
    {
        // 0-99 are probably going to be used for local system accounts instead of LDAP accounts
        // 100-999, 60000-64999 are reserved for debian packages
        return $id <= 999 || ($id >= 60000 && $id <= 64999);
    }

    private function getNextIDNumber($start, $IDsToSkip)
    {
        $new_id = $start;
        while (
            $this->isIDNumberForbidden($new_id) ||
            in_array($new_id, $IDsToSkip)
        ) {
            $new_id++;
        }
        return $new_id;
    }

    private function getCustomIDMappings()
    {
        $output = [];
        $dir = new \DirectoryIterator($this->custom_mappings_path);
        foreach ($dir as $fileinfo) {
            $filename = $fileinfo->getFilename();
            if ($fileinfo->isDot() || $filename == "README.md") {
                continue;
            }
            if ($fileinfo->getExtension() == "csv") {
                $handle = fopen($fileinfo->getPathname(), "r");
                while (($row = fgetcsv($handle, null, ",")) !== false) {
                    array_push($output, $row);
                }
            } else {
                UnityHTTPD::errorLog(
                    "warning",
                    "custom ID mapping file '$filename' ignored, extension != .csv",
                );
            }
        }
        $output_map = [];
        foreach ($output as [$uid, $uidNumber_str]) {
            $output_map[$uid] = intval($uidNumber_str);
        }
        return $output_map;
    }

    private function getAllUIDNumbersInUse()
    {
        // use baseOU for awareness of externally managed entries
        return array_map(
            fn($x) => $x["uidnumber"][0],
            $this->baseOU->getChildrenArray(
                ["uidNumber"],
                true,
                "(objectClass=posixAccount)",
            ),
        );
    }

    private function getAllGIDNumbersInUse()
    {
        // use baseOU for awareness of externally managed entries
        return array_map(
            fn($x) => $x["gidnumber"][0],
            $this->baseOU->getChildrenArray(
                ["gidNumber"],
                true,
                "(objectClass=posixGroup)",
            ),
        );
    }

    public function getAllUsersUIDs()
    {
        // should not use $user_ou->getChildren or $base_ou->getChildren(objectClass=posixAccount)
        // Unity users might be outside user ou, and not all users in LDAP tree are unity users
        return $this->userGroup->getAttribute("memberuid");
    }

    public function getAllUsers(
        $UnitySQL,
        $UnityMailer,
        $UnityRedis,
        $UnityWebhook,
        $ignorecache = false,
    ) {
        $out = [];

        if (!$ignorecache) {
            $users = $UnityRedis->getCache("sorted_users", "");
            if (!is_null($users)) {
                foreach ($users as $user) {
                    array_push(
                        $out,
                        new UnityUser(
                            $user,
                            $this,
                            $UnitySQL,
                            $UnityMailer,
                            $UnityRedis,
                            $UnityWebhook,
                        ),
                    );
                }
                return $out;
            }
        }

        $users = $this->getAllUsersUIDs();
        sort($users);
        foreach ($users as $user) {
            $params = [
                $user,
                $this,
                $UnitySQL,
                $UnityMailer,
                $UnityRedis,
                $UnityWebhook,
            ];
            array_push($out, new UnityUser(...$params));
        }
        return $out;
    }

    public function getAllUsersAttributes($attributes)
    {
        $include_uids = $this->getAllUsersUIDs();
        $user_attributes = $this->baseOU->getChildrenArray(
            $attributes,
            true, // recursive
            "(objectClass=posixAccount)",
        );
        foreach ($user_attributes as $i => $attributes) {
            if (!in_array($attributes["uid"][0], $include_uids)) {
                unset($user_attributes[$i]);
            }
        }
        return array_values($user_attributes); // reindex
    }

    public function getAllPIGroups(
        $UnitySQL,
        $UnityMailer,
        $UnityRedis,
        $UnityWebhook,
        $ignorecache = false,
    ) {
        $out = [];

        if (!$ignorecache) {
            $groups = $UnityRedis->getCache("sorted_groups", "");
            if (!is_null($groups)) {
                foreach ($groups as $group) {
                    $params = [
                        $group,
                        $this,
                        $UnitySQL,
                        $UnityMailer,
                        $UnityRedis,
                        $UnityWebhook,
                    ];
                    array_push($out, new UnityGroup(...$params));
                }

                return $out;
            }
        }

        $pi_groups = $this->pi_groupOU->getChildren(true);

        foreach ($pi_groups as $pi_group) {
            array_push(
                $out,
                new UnityGroup(
                    $pi_group->getAttribute("cn")[0],
                    $this,
                    $UnitySQL,
                    $UnityMailer,
                    $UnityRedis,
                    $UnityWebhook,
                ),
            );
        }

        return $out;
    }

    public function getAllPIGroupsAttributes($attributes)
    {
        return $this->pi_groupOU->getChildrenArray($attributes);
    }

    public function getPIGroupGIDsWithMemberUID($uid)
    {
        return array_map(
            fn($x) => $x["cn"][0],
            $this->pi_groupOU->getChildrenArray(
                ["cn"],
                false,
                "(memberuid=" . ldap_escape($uid, LDAP_ESCAPE_FILTER) . ")",
            ),
        );
    }

    public function getAllPIGroupOwnerAttributes($attributes)
    {
        // get the PI groups, filter for just the GIDs, then map the GIDs to owner UIDs
        $owner_uids = array_map(
            fn($x) => UnityGroup::GID2OwnerUID($x),
            array_map(
                fn($x) => $x["cn"][0],
                $this->pi_groupOU->getChildrenArray(["cn"]),
            ),
        );
        $owner_attributes = $this->getAllUsersAttributes($attributes);
        foreach ($owner_attributes as $i => $attributes) {
            if (!in_array($attributes["uid"][0], $owner_uids)) {
                unset($owner_attributes[$i]);
            }
        }
        $owner_attributes = array_values($owner_attributes); // reindex
        $owners_not_found = array_diff(
            $owner_uids,
            array_map(fn($x) => $x["uid"][0], $owner_attributes),
        );
        if (count($owners_not_found) > 0) {
            UnityHTTPD::errorLog(
                "warning",
                "PI group owners not found: " .
                    \jsonEncode($owners_not_found) .
                    "\n",
            );
        }
        return $owner_attributes;
    }

    /**
     * Returns an associative array where keys are UIDs and values are arrays of PI GIDs
     */
    public function getAllUID2PIGIDs()
    {
        // initialize output so each UID is a key with an empty array as its value
        $uids = $this->getAllUsersUIDs();
        $uid2pigids = array_combine($uids, array_fill(0, count($uids), []));
        // for each PI group, append that GID to the member list for each of its member UIDs
        foreach (
            $this->getAllPIGroupsAttributes(["cn", "memberuid"])
            as $array
        ) {
            $gid = $array["cn"][0];
            foreach ($array["memberuid"] as $uid) {
                if (array_key_exists($uid, $uid2pigids)) {
                    array_push($uid2pigids[$uid], $gid);
                } else {
                    UnityHTTPD::errorLog(
                        "warning",
                        "user '$uid' is a member of a PI group but is not a Unity user!",
                    );
                }
            }
        }
        return $uid2pigids;
    }

    public function getAllOrgGroups(
        $UnitySQL,
        $UnityMailer,
        $UnityRedis,
        $UnityWebhook,
        $ignorecache = false,
    ) {
        $out = [];

        if (!$ignorecache) {
            $orgs = $UnityRedis->getCache("sorted_orgs", "");
            if (!is_null($orgs)) {
                foreach ($orgs as $org) {
                    array_push(
                        $out,
                        new UnityOrg(
                            $org,
                            $this,
                            $UnitySQL,
                            $UnityMailer,
                            $UnityRedis,
                            $UnityWebhook,
                        ),
                    );
                }
                return $out;
            }
        }

        $org_groups = $this->org_groupOU->getChildren(true);

        foreach ($org_groups as $org_group) {
            array_push(
                $out,
                new UnityOrg(
                    $org_group->getAttribute("cn")[0],
                    $this,
                    $UnitySQL,
                    $UnityMailer,
                    $UnityRedis,
                    $UnityWebhook,
                ),
            );
        }

        return $out;
    }

    public function getAllOrgGroupsAttributes($attributes)
    {
        return $this->org_groupOU->getChildrenArray($attributes);
    }

    public function getUserEntry($uid)
    {
        $uid = ldap_escape($uid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(
            unityLDAP::RDN . "=$uid," . CONFIG["ldap"]["user_ou"],
        );
    }

    public function getGroupEntry($gid)
    {
        $gid = ldap_escape($gid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(
            unityLDAP::RDN . "=$gid," . CONFIG["ldap"]["group_ou"],
        );
    }

    public function getPIGroupEntry($gid)
    {
        $gid = ldap_escape($gid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(
            unityLDAP::RDN . "=$gid," . CONFIG["ldap"]["pigroup_ou"],
        );
    }

    public function getOrgGroupEntry($gid)
    {
        $gid = ldap_escape($gid, "", LDAP_ESCAPE_DN);
        return $this->getEntry(
            unityLDAP::RDN . "=$gid," . CONFIG["ldap"]["orggroup_ou"],
        );
    }
}
