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
    private const ID_MAP = array(1000, 9999);
    private const PI_ID_MAP = array(10000, 19999);
    private const ORG_ID_MAP = array(20000, 29999);

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
    private $STR_USEROU;
    private $STR_GROUPOU;
    private $STR_PIGROUPOU;
    private $STR_ORGGROUPOU;
    private $STR_ADMINGROUP;

  // Instance vars for various ldapEntry objects
    private $userOU;
    private $groupOU;
    private $pi_groupOU;
    private $org_groupOU;
    private $adminGroup;

    private $custom_mappings_path;

    private $def_user_shell;

    public function __construct(
        $host,
        $dn,
        $pass,
        $custom_user_mappings,
        $user_ou,
        $group_ou,
        $pigroup_ou,
        $orggroup_ou,
        $admin_group,
        $def_user_shell
    ) {
        parent::__construct($host, $dn, $pass);

        $this->STR_USEROU = $user_ou;
        $this->STR_GROUPOU = $group_ou;
        $this->STR_PIGROUPOU = $pigroup_ou;
        $this->STR_ORGGROUPOU = $orggroup_ou;
        $this->STR_ADMINGROUP = $admin_group;

      // Get Global Entries
        $this->userOU = $this->getEntry($user_ou);
        $this->groupOU = $this->getEntry($group_ou);
        $this->pi_groupOU = $this->getEntry($pigroup_ou);
        $this->org_groupOU = $this->getEntry($orggroup_ou);
        $this->adminGroup = $this->getEntry($admin_group);

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

    public function getDefUserShell()
    {
        return $this->def_user_shell;
    }

  //
  // ID Number selection functions
  //
    public function getNextUIDNumber(UnitySQL $UnitySQL): int
    {
        $new_id = $UnitySQL->getSiteVar('MAX_UID') + 1;
        while ($this->IDNumInUse($new_id)) {
            $new_id++;
        }
        $UnitySQL->updateSiteVar('MAX_UID', $new_id);
        return $new_id;
    }

    public function getNextPiGIDNumber(UnitySQL $UnitySQL): int
    {
        $max_pigid = $UnitySQL->getSiteVar('MAX_PIGID');
        $new_pigid = $max_pigid + 1;
        while ($this->IDNumInUse($new_pigid)) {
            $new_pigid++;
        }
        $UnitySQL->updateSiteVar('MAX_PIGID', $new_pigid);
        return $new_pigid;
    }

    public function getNextOrgGIDNumber(UnitySQL $UnitySQL): int
    {
        $max_gid = $UnitySQL->getSiteVar('MAX_GID');
        $new_gid = $max_gid + 1;
        while ($this->IDNumInUse($new_gid)) {
            $new_gid++;
        }
        $UnitySQL->updateSiteVar('MAX_GID', $new_gid);
        return $new_gid;
    }

    private function IDNumInUse(int $id): bool
    {
        // id reserved for debian packages
        if (($id >= 100 && $id <= 999) || ($id >= 60000 && $id <= 64999)) {
            return true;
        }
        $users = $this->userOU->getChildrenArray(true);
        foreach ($users as $user) {
            if ($user["uidnumber"][0] == $id) {
                return true;
            }
        }
        $pi_groups = $this->pi_groupOU->getChildrenArray(true);
        foreach ($pi_groups as $pi_group) {
            if ($pi_group["gidnumber"][0] == $id) {
                return true;
            }
        }
        $groups = $this->groupOU->getChildrenArray(true);
        foreach ($groups as $group) {
            if ($group["gidnumber"][0] == $id) {
                return true;
            }
        }
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

        $users = $this->userOU->getChildren(true);

        foreach ($users as $user) {
            $params = array($user->getAttribute("cn")[0], $this, $UnitySQL, $UnityMailer, $UnityRedis, $UnityWebhook);
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
        $ldap_entry = new LDAPEntry($this->getConn(), unityLDAP::RDN . "=$uid," . $this->STR_USEROU);
        return $ldap_entry;
    }

    public function getGroupEntry($gid)
    {
        $ldap_entry = new LDAPEntry($this->getConn(), unityLDAP::RDN . "=$gid," . $this->STR_GROUPOU);
        return $ldap_entry;
    }

    public function getPIGroupEntry($gid)
    {
        $ldap_entry = new LDAPEntry($this->getConn(), unityLDAP::RDN . "=$gid," . $this->STR_PIGROUPOU);
        return $ldap_entry;
    }

    public function getOrgGroupEntry($gid)
    {
        $ldap_entry = new LDAPEntry($this->getConn(), unityLDAP::RDN . "=$gid," . $this->STR_ORGGROUPOU);
        return $ldap_entry;
    }
}
