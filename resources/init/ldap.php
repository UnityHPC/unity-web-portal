<?php
/* REQUIRES /resources/config.php, and /resources/autoload/shib.php */

if (isset($SHIB)) {  // Check if shibboleth has been initialized
    // Initialize LDAP Connection
  $ldap = new unityLDAP(config::LDAP["host"], config::LDAP["dn"], config::LDAP["pass"]);  // This is the primary LDAP connection var
  $user = new unityUser($SHIB["netid"], $ldap, $sql, $sacctmgr);  // This is the current user's unity account object

  if ($user->exists()) {
      $_SESSION["user-state"] = "present";
      $_SESSION["is_pi"] = $user->isPI();
  } else {
    // User does not exist on the LDAP
    $_SESSION["user-state"] = "none";
    redirect(config::PREFIX . "/panel/new_account.php");
  }
}