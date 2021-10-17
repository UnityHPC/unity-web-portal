<?php

require_once "ldap.php";  // Load Parent LDAP classes

/**
 * An LDAP connection class which extends ldapConn tailored for the Unity Cluster
 */
class unityLDAP extends ldapConn
{
  // Tree Constants
  const BASE = "dc=unity,dc=rc,dc=umass,dc=edu";
  // Units (relative to base DN)
  const USERS = "ou=users," . self::BASE;
  const GROUPS = "ou=groups," . self::BASE;
  const PI_GROUPS = "ou=pi_groups," . self::BASE;
  const ADMIN_GROUP = "cn=sudo," . self::BASE;

  const STOR_PREFIX = "storage_";

  // User Specific Constants
  const ID_MAP = array(1000, 9999);
  const PI_ID_MAP = array(10000, 19999);

  const RDN = "cn";  // The defauls RDN for LDAP entries is set to "common name"

  const DEFAULT_SHELL = "/bin/bash";

  const POSIX_ACCOUNT_CLASS = array(
    "inetorgperson",
    "posixAccount",
    "top",
    "ldapPublicKey"
  );

  const POSIX_GROUP_CLASS = array(
    "posixGroup",
    "top"
  );

  // Instance vars for various ldapEntry objects
  public $userOU;
  public $groupOU;
  public $storageOU;
  public $adminGroup;
  public $pi_groupOU;
  public $shared_groupOU;

  public function __construct($host, $dn, $pass)
  {
    parent::__construct($host, $dn, $pass);

    // Get Global Entries
    $this->userOU = $this->getEntry(self::USERS);
    $this->groupOU = $this->getEntry(self::GROUPS);
    $this->pi_groupOU = $this->getEntry(self::PI_GROUPS);
    $this->adminGroup = $this->getEntry(self::ADMIN_GROUP);
  }

  public function getNextUID()
  {
    $users = $this->userOU->getChildrenArray(true);  // ! Restore this instead of temporary

    usort($users, function ($a, $b) {
      return $a["uidnumber"] <=> $b["uidnumber"];
    });

    $id = self::ID_MAP[0];
    foreach ($users as $acc) {
      if ($id == $acc["uidnumber"][0]) {
        $id++;
        if ($id > self::ID_MAP[1]) {
          throw new Exception("UID Limits reached");  // all hell has broken if this executes
        }
      } else {
        break;
      }
    }

    return $id;
  }

  public function getNextGID()
  {
    $groups = $this->groupOU->getChildrenArray(true);

    usort($groups, function ($a, $b) {
      return $a["gidnumber"] <=> $b["gidnumber"];
    });

    $id = self::ID_MAP[0];
    foreach ($groups as $acc) {
      if ($id == $acc["gidnumber"][0]) {
        $id++;
        if ($id > self::ID_MAP[1]) {
          throw new Exception("GID Limits reached");  // all hell has broken if this executes
        }
      } else {
        break;
      }
    }

    return $id;
  }

  public function getNextPiGID()
  {
    $groups = $this->pi_groupOU->getChildrenArray(true);

    usort($groups, function ($a, $b) {
      return $a["gidnumber"] <=> $b["gidnumber"];
    });

    $id = self::PI_ID_MAP[0];
    foreach ($groups as $acc) {
      if ($id == $acc["gidnumber"][0]) {
        $id++;
        if ($id > self::PI_ID_MAP[1]) {
          throw new Exception("Storage GID Limits reached");  // all hell has broken if this executes
        }
      } else {
        break;
      }
    }

    return $id;
  }

  public function getAllRecipients()
  {
    $users = $this->userOU->getChildren(true);

    $out = array();
    foreach ($users as $user) {
      array_push($out, $user->getAttribute("mail")[0]);
    }

    return $out;
  }

  private function UIDNumInUse($id) {
    $users = $this->userOU->getChildrenArray(true);
    foreach ($users as $user) {
      if ($user["uidnumber"][0] == $id) {
        return true;
      }
    }

    return false;
  }

  private function GIDNumInUse($id) {
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
    $dir = new DirectoryIterator(config::PATHS["custom_user"]);
    foreach ($dir as $fileinfo) {
      if ($fileinfo->getExtension() == "csv") {
        // found csv file
        $handle = fopen($fileinfo->getPathname(), "r");
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
    $next_uid = $this->getNextUID();
    if ($this->GIDNumInUse($next_uid)) {
      throw new Exception("UID/GID Mismatch");
    }

    return $next_uid;
  }

  public function getAllUsers($services)
  {
    $users = $this->userOU->getChildren(true);

    $out = array();
    foreach ($users as $user) {
      array_push($out, new unityUser($user->getAttribute("cn")[0], $services));
    }

    return $out;
  }
}
