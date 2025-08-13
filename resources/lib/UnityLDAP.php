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
        $base_dn,
        $user_ou,
        $group_ou,
        $pigroup_ou,
        $orggroup_ou,
        $admin_group,
        $user_group_dn,
        $def_user_shell
    ) {
        parent::__construct($host, $dn, $pass);

        $this->STR_BASEOU = $base_dn;
        $this->STR_USEROU = $user_ou;
        $this->STR_GROUPOU = $group_ou;
        $this->STR_PIGROUPOU = $pigroup_ou;
        $this->STR_ORGGROUPOU = $orggroup_ou;
        $this->STR_ADMINGROUP = $admin_group;

      // Get Global Entries
        $this->baseOU = $this->getEntry($base_dn);
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
        $id_nums_in_use = $this->getIDNumsInUse();
        while ($this->IDNumInUse($new_uid, $id_nums_in_use)) {
            $new_uid++;
        }
        $UnitySQL->updateSiteVar('MAX_UID', $new_uid);
        return $new_uid;
    }

    public function getNextPiGIDNumber($UnitySQL)
    {
        $max_pigid = $UnitySQL->getSiteVar('MAX_PIGID');
        $new_pigid = $max_pigid + 1;
        $id_nums_in_use = $this->getIDNumsInUse();
        while ($this->IDNumInUse($new_pigid, $id_nums_in_use)) {
            $new_pigid++;
        }
        $UnitySQL->updateSiteVar('MAX_PIGID', $new_pigid);
        return $new_pigid;
    }

    public function getNextOrgGIDNumber($UnitySQL)
    {
        $max_gid = $UnitySQL->getSiteVar('MAX_GID');
        $new_gid = $max_gid + 1;
        $id_nums_in_use = $this->getIDNumsInUse();
        while ($this->IDNumInUse($new_gid, $id_nums_in_use)) {
            $new_gid++;
        }
        $UnitySQL->updateSiteVar('MAX_GID', $new_gid);
        return $new_gid;
    }

    private function IDNumInUse($id_num, $id_nums_in_use)
    {
        // 0-99 are probably going to be used for local system accounts instead of LDAP accounts
        // 100-999, 60000-64999 are reserved for debian packages
        if (($id_num <= 999) || ($id_num >= 60000 && $id_num <= 64999)) {
            return true;
        }
        return in_array($id_num, $id_nums_in_use);
    }

    private function getIDNumsInUse()
    {
        return array_merge(
            // search entire LDAP tree, not just for entries created by portal
            array_map(
                fn($x) => intval($x["uidnumber"][0]),
                $this->baseOU->getChildrenArray(["uidnumber"], true, "objectClass=posixAccount")
            ),
            array_map(
                fn($x) => intval($x["gidnumber"][0]),
                $this->baseOU->getChildrenArray(["gidnumber"], true, "objectClass=posixGroup")
            ),
        );
    }

    public function getUnassignedID($uid, $UnitySQL)
    {
        $id_nums_in_use = $this->getIDNumsInUse();
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
                        if (!$this->IDNumInUse($uid_match, $id_nums_in_use)) {
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

        $users = $this->userGroup->getAttribute("memberuid");
        sort($users);
        foreach ($users as $user) {
            $params = array($user, $this, $UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook);
            array_push($out, new UnityUser(...$params));
        }

        return $out;
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
