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
  const STORAGE = "ou=storage," . self::BASE;
  const PI_GROUPS = "ou=pi_groups," . self::BASE;
  const ADMIN_GROUP = "cn=sudo," . self::BASE;

  const STOR_PREFIX = "storage_";

  // User Specific Constants
  const ID_MAP = array(1000, 9999);
  const PI_ID_MAP = array(10000, 19999);
  const STOR_ID_MAP = array(20000, 29999);  // ! Not yet implemented

  const RDN = "cn";  // The defauls RDN for LDAP entries is set to "common name"

  const NOLOGIN_SHELL = "/sbin/nologin";
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

  public function __construct($host, $dn, $pass)
  {
    parent::__construct($host, $dn, $pass);

    // Get Global Entries
    $this->userOU = $this->getEntry(self::USERS);
    $this->groupOU = $this->getEntry(self::GROUPS);
    $this->storageOU = $this->getEntry(self::STORAGE);
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

  public function getNextGID() {
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

  public function getNextStorGID() {
    $groups = $this->storOU->getChildrenArray(true);

    usort($groups, function ($a, $b) {
      return $a["gidnumber"] <=> $b["gidnumber"];
    });

    $id = self::STOR_ID_MAP[0];
    foreach ($groups as $acc) {
      if ($id == $acc["gidnumber"][0]) {
        $id++;
        if ($id > self::STOR_ID_MAP[1]) {
          throw new Exception("Storage GID Limits reached");  // all hell has broken if this executes
        }
      } else {
        break;
      }
    }

    return $id;
  }

  public function getNextPiGID() {
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

  public function getAllRecipients() {
    $users = $this->userOU->getChildren(true);

    $out = array();
    foreach ($users as $user) {
      array_push($out, $user->getAttribute("mail")[0]);
    }

    return $out;
  }
}