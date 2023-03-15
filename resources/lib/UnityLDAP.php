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
    public function getNextUIDNumber()
    {
        $users = $this->userOU->getChildrenArray(true);

      // This could become inefficient with more users
        usort($users, function ($a, $b) {
            return $a["uidnumber"] <=> $b["uidnumber"];
        });

        $id = self::ID_MAP[0];
        foreach ($users as $acc) {
            if ($id == $acc["uidnumber"][0]) {
                $id++;
            } else {
                if (!$this->GIDNumInUse($id)) {
                    break;
                }
            }
        }

        return $id;
    }

    public function getNextPiGIDNumber()
    {
        $groups = $this->pi_groupOU->getChildrenArray(true);

        usort($groups, function ($a, $b) {
            return $a["gidnumber"] <=> $b["gidnumber"];
        });

        $id = self::PI_ID_MAP[0];
        foreach ($groups as $acc) {
            if ($id == $acc["gidnumber"][0]) {
                $id++;
            } else {
                break;
            }
        }

        return $id;
    }

    public function getNextOrgGIDNumber()
    {
        $groups = $this->org_groupOU->getChildrenArray(true);

        usort($groups, function ($a, $b) {
            return $a["gidnumber"] <=> $b["gidnumber"];
        });

        $id = self::ORG_ID_MAP[0];
        foreach ($groups as $acc) {
            if ($id == $acc["gidnumber"][0]) {
                $id++;
            } else {
                break;
            }
        }

        return $id;
    }

    private function UIDNumInUse($id)
    {
        $users = $this->userOU->getChildrenArray(true);
        foreach ($users as $user) {
            if ($user["uidnumber"][0] == $id) {
                return true;
            }
        }

        return false;
    }

    private function GIDNumInUse($id)
    {
        $users = $this->groupOU->getChildrenArray(true);
        foreach ($users as $user) {
            if ($user["gidnumber"][0] == $id) {
                return true;
            }
        }

        return false;
    }

    public function getUnassignedID($uid)
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
                        if (!$this->UIDNumInUse($uid_match) && !$this->GIDNumInUse($uid_match)) {
                            return $uid_match;
                        }
                    }
                }
            }
        }

      // didn't find anything from existing mappings, use next available
        $next_uid = $this->getNextUIDNumber();

        return $next_uid;
    }

  //
  // Functions that return user/group objects
  //
    public function getAllUsers($UnitySQL, $UnityMailer, $UnityRedis, $ignorecache = false)
    {
        $out = array();

        if (!$ignorecache) {
            $users = $UnityRedis->getCache("sorted_users", "");
            if (!is_null($users)) {
                foreach ($users as $user) {
                    array_push($out, new UnityUser($user, $this, $UnitySQL, $UnityMailer, $UnityRedis));
                }

                return $out;
            }
        }

        $users = $this->userOU->getChildren(true);

        foreach ($users as $user) {
            array_push($out, new UnityUser($user->getAttribute("cn")[0], $this, $UnitySQL, $UnityMailer, $UnityRedis));
        }

        return $out;
    }

    public function getAllPIGroups($UnitySQL, $UnityMailer, $UnityRedis, $ignorecache = false)
    {
        $out = array();

        if (!$ignorecache) {
            $groups = $UnityRedis->getCache("sorted_groups", "");
            if (!is_null($groups)) {
                foreach ($groups as $group) {
                    array_push($out, new UnityGroup($group, $this, $UnitySQL, $UnityMailer, $UnityRedis));
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
                $UnityRedis
            ));
        }

        return $out;
    }

    public function getAllOrgGroups($UnitySQL, $UnityMailer, $UnityRedis, $ignorecache = false)
    {
        $out = array();

        if (!$ignorecache) {
            $orgs = $UnityRedis->getCache("sorted_orgs", "");
            if (!is_null($orgs)) {
                foreach ($orgs as $org) {
                    array_push($out, new UnityOrg($org, $this, $UnitySQL, $UnityMailer, $UnityRedis));
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
                $UnityRedis
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
