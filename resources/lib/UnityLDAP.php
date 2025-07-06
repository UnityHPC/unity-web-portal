<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPConn;
use PHPOpenLDAPer\LDAPEntry;

/**
 * An LDAP connection class which extends ldapConn tailored for the Unity Cluster
 */
class UnityLDAP extends ldapConn
{
  // User Specific Constants
    private const RDN = "cn";  // The defauls RDN for LDAP entries is set to "common name"

    public const POSIX_ACCOUNT_CLASS = array(
    "inetorgperson",
    "posixAccount",
    "top",
    "ldapPublicKey"
    );

    public const POSIX_GROUP_CLASS = array(
    "posixGroup",
    "top"
    );

  // string vars for OUs
    private $STR_BASEOU;
    private $STR_USEROU;
    private $STR_GROUPOU;
    private $STR_PIGROUPOU;
    private $STR_ORGGROUPOU;
    private $STR_ADMINGROUP;

  // Instance vars for various ldapEntry objects
    private $baseOU;
    private $userOU;
    private $groupOU;
    private $pi_groupOU;
    private $org_groupOU;
    private $adminGroup;
    private $userGroup;

    private $custom_mappings_path;

    private $def_user_shell;

    public function __construct(
        $host,
        $dn,
        $pass,
        $custom_user_mappings,
        $base_ou,
        $user_ou,
        $group_ou,
        $pigroup_ou,
        $orggroup_ou,
        $admin_group,
        $user_group_dn,
        $def_user_shell
    ) {
        parent::__construct($host, $dn, $pass);

        $this->STR_BASEOU = $base_ou;
        $this->STR_USEROU = $user_ou;
        $this->STR_GROUPOU = $group_ou;
        $this->STR_PIGROUPOU = $pigroup_ou;
        $this->STR_ORGGROUPOU = $orggroup_ou;
        $this->STR_ADMINGROUP = $admin_group;

      // Get Global Entries
        $this->baseOU = $this->getEntry($base_ou);
        $this->userOU = $this->getEntry($user_ou);
        $this->groupOU = $this->getEntry($group_ou);
        $this->pi_groupOU = $this->getEntry($pigroup_ou);
        $this->org_groupOU = $this->getEntry($orggroup_ou);
        $this->adminGroup = $this->getEntry($admin_group);
        $this->userGroup = $this->getEntry($user_group_dn);

        $this->custom_mappings_path = $custom_user_mappings;

        $this->def_user_shell = $def_user_shell;
    }

  //
  // Get methods for OU
  //
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

  //
  // ID Number selection functions
  //
    public function getNextUIDNumber($UnitySQL)
    {
        $max_uid = $UnitySQL->getSiteVar('MAX_UID');
        $new_uid = $max_uid + 1;

        while ($this->IDNumInUse($new_uid)) {
            $new_uid++;
        }

        $UnitySQL->updateSiteVar('MAX_UID', $new_uid);

        return $new_uid;
    }

    public function getNextPiGIDNumber($UnitySQL)
    {
        $max_pigid = $UnitySQL->getSiteVar('MAX_PIGID');
        $new_pigid = $max_pigid + 1;

        while ($this->IDNumInUse($new_pigid)) {
            $new_pigid++;
        }

        $UnitySQL->updateSiteVar('MAX_PIGID', $new_pigid);

        return $new_pigid;
    }

    public function getNextOrgGIDNumber($UnitySQL)
    {
        $max_gid = $UnitySQL->getSiteVar('MAX_GID');
        $new_gid = $max_gid + 1;

        while ($this->IDNumInUse($new_gid)) {
            $new_gid++;
        }

        $UnitySQL->updateSiteVar('MAX_GID', $new_gid);

        return $new_gid;
    }

    private function IDNumInUse($id)
    {
        // 0-99 are probably going to be used for local system accounts instead of LDAP accounts
        // 100-999, 60000-64999 are reserved for debian packages
        if (($id <= 999) || ($id >= 60000 && $id <= 64999)) {
            return true;
        }
        $users = $this->userOU->getChildrenArray([], true);
        foreach ($users as $user) {
            if ($user["uidnumber"][0] == $id) {
                return true;
            }
        }
        $pi_groups = $this->pi_groupOU->getChildrenArray(["gidnumber"], true);
        foreach ($pi_groups as $pi_group) {
            if ($pi_group["gidnumber"][0] == $id) {
                return true;
            }
        }
        $groups = $this->groupOU->getChildrenArray(["gidnumber"], true);
        foreach ($groups as $group) {
            if ($group["gidnumber"][0] == $id) {
                return true;
            }
        }

        return false;
    }

    public function getUnassignedID($uid, $UnitySQL)
    {
        $netid = strtok($uid, "_");  // extract netid
      // scrape all files in custom folder
        $dir = new \DirectoryIterator($this->custom_mappings_path);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->getExtension() == "csv") {
                // found csv file
                $handle = fopen($fileinfo->getPathname(), "r");
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $netid_match = $data[0];
                    $uid_match = $data[1];

                    if ($uid == $netid_match || $netid == $netid_match) {
                        // found a match
                        if (!$this->IDNumInUse($uid_match)) {
                            return $uid_match;
                        }
                    }
                }
            }
        }

      // didn't find anything from existing mappings, use next available
        $next_uid = $this->getNextUIDNumber($UnitySQL);

        return $next_uid;
    }

    public function getAllUsersUIDs()
    {
        // should not use $user_ou->getChildren or $this->search(objectClass=posixAccount, $base_dn)
        // Unity users might be outside user ou, and not all users in LDAP tree are unity users
        return $this->userGroup->getAttribute("memberuid");
    }

  //
  // Functions that return user/group objects
  //
    public function getAllUsers($UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook, $ignorecache = false)
    {
        $out = array();

        if (!$ignorecache) {
            $users = $UnityRedis->getCache("sorted_users", "");
            if (!is_null($users)) {
                foreach ($users as $user) {
                    array_push($out, new UnityUser($user, $this, $UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook));
                }

                return $out;
            }
        }

        $users = $this->getAllUsersUIDs();
        sort($users);
        foreach ($users as $user) {
            $params = array($user, $this, $UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook);
            array_push($out, new UnityUser(...$params));
        }
    }

    public function getAllUsersEntries()
    {
        $include_uids = $this->getAllUsersUIDs();
        $user_entries = $this->baseOU->getChildren(
            [], // all attributes
            true, // recursive
            "objectClass=posixAccount"
        );
        foreach ($user_entries as $i => $entry) {
            if (!in_array($entry["uid"], $include_uids)) {
                unset($user_entries[$i]);
            }
        }
        return $user_entries;
    }

    public function getAllPIGroups($UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook, $ignorecache = false)
    {
        $out = array();

        if (!$ignorecache) {
            $groups = $UnityRedis->getCache("sorted_groups", "");
            if (!is_null($groups)) {
                foreach ($groups as $group) {
                    $params = array($group, $this, $UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook);
                    array_push($out, new UnityGroup(...$params));
                }

                return $out;
            }
        }

        $pi_groups = $this->pi_groupOU->getChildren(true);

        foreach ($pi_groups as $pi_group) {
            array_push($out, new UnityGroup(
                $pi_group->getAttribute("cn")[0],
                $this,
                $UnitySQL,
                $UnityMailer,
                $UnityRedis,
                $UnityWebhook
            ));
        }

        return $out;
    }

    public function getAllPIGroupsEntries()
    {
        return $this->pi_groupOU->getChildrenArray(true);
    }

    /** Returns an assosiative array where keys are UIDs and values are lists of PI GIDs */
    public function getAllUID2PIGIDs()
    {
        // initialize output so each UID is a key with an empty array as its value
        $UID2PIGIDs = array_combine(
            $this->getAllUsersUIDs(),
            array_map(
                fn($x) => [],
                $this->getAllUsersUIDs()
            )
        );
        // for each PI group, append that GID to the member list for each of its member UIDs
        foreach ($this->getAllPIGroupsEntries() as $entry) {
            $gid = $entry["cn"];
            foreach ($entry["memberUid"] as $uid) {
                array_push($UID2PIGIDs[$uid], $gid);
            }
        }
        return $UID2PIGIDs;
    }

    public function getAllOrgGroups($UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook, $ignorecache = false)
    {
        $out = array();

        if (!$ignorecache) {
            $orgs = $UnityRedis->getCache("sorted_orgs", "");
            if (!is_null($orgs)) {
                foreach ($orgs as $org) {
                    array_push($out, new UnityOrg($org, $this, $UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook));
                }

                return $out;
            }
        }

        $org_groups = $this->org_groupOU->getChildren(true);

        foreach ($org_groups as $org_group) {
            array_push($out, new UnityOrg(
                $org_group->getAttribute("cn")[0],
                $this,
                $UnitySQL,
                $UnityMailer,
                $UnityRedis,
                $UnityWebhook
            ));
        }

        return $out;
    }

    public function getAllOrgGroupsEntries()
    {
        return $this->org_groupOU->getChildrenArray(true);
    }

    public function getUserEntry($uid)
    {
        $uid = ldap_escape($uid, LDAP_ESCAPE_DN);
        return $this->getEntry(unityLDAP::RDN . "=$uid," . $this->STR_USEROU);
    }

    public function getGroupEntry($gid)
    {
        $gid = ldap_escape($gid, LDAP_ESCAPE_DN);
        return $this->getEntry(unityLDAP::RDN . "=$gid," . $this->STR_GROUPOU);
    }

    public function getPIGroupEntry($gid)
    {
        $gid = ldap_escape($gid, LDAP_ESCAPE_DN);
        return $this->getEntry(unityLDAP::RDN . "=$gid," . $this->STR_PIGROUPOU);
    }

    public function getOrgGroupEntry($gid)
    {
        $gid = ldap_escape($gid, LDAP_ESCAPE_DN);
        return $this->getEntry(unityLDAP::RDN . "=$gid," . $this->STR_ORGGROUPOU);
    }
}
