<?php
/* REQUIRED /resources/config.php */

//
// Shibboleth and LDAP Initialization
//

function EPPN_to_uid($eppn) {
  $eppnExpanded = explode("@", $eppn);
  return $eppnExpanded[0] . "_" . str_replace(".", "_", $eppnExpanded[1]);
}

if (isset($_SERVER["REMOTE_USER"])) {  // Check if SHIB is enabled on this page
  // Set Shibboleth Session Vars - Vars stored in session to be accessible outside shib-controlled areas of the sites (ie contact page)
  $SHIB = array(
    "netid" => EPPN_to_uid($_SERVER["REMOTE_USER"]),
    "firstname" => $_SERVER["givenName"],
    "lastname" => $_SERVER["sn"],
    "name" => $_SERVER["givenName"] . " " . $_SERVER["sn"],
    "mail" => isset($_SERVER["mail"]) ? $_SERVER["mail"] : $_SERVER["eppn"]  // Fallback to EPPN if mail is not set
  );
  $_SESSION["SHIB"] = $SHIB;  // Set the session var for non-authenticated pages
}