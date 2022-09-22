<?php

function EPPN_to_uid($eppn)
{
    $eppn_output = str_replace(".", "_", $eppn);
    $eppn_output = str_replace("@", "_", $eppn_output);
    return strtolower($eppn_output);
}

function EPPN_to_org($eppn)
{
    $parts = explode("@", $eppn);
    if (count($parts) != 2) {
        throw new Exception("Malformed remote user detected");
    }

    $org = $parts[1];
    $org = str_replace(".", "_", $org);
    return strtolower($org);
}

if (isset($_SERVER["REMOTE_USER"])) {  // Check if SSO is enabled on this page
    // Set SSO Session Vars - Vars stored in session to be accessible outside shib-controlled areas of the sites (ie contact page)
    $SSO = array(
        "user" => EPPN_to_uid($_SERVER["REMOTE_USER"]),
        "org" => EPPN_to_org($_SERVER["REMOTE_USER"]),
        "firstname" => $_SERVER["givenName"],
        "lastname" => $_SERVER["sn"],
        "name" => $_SERVER["givenName"] . " " . $_SERVER["sn"],
        "mail" => isset($_SERVER["mail"]) ? $_SERVER["mail"] : $_SERVER["eppn"]  // Fallback to EPPN if mail is not set
    );
    $_SESSION["SSO"] = $SSO;  // Set the session var for non-authenticated pages

    // add sso login entry to mysql table
    $SQL->addSSOEntry(
        $SSO["user"],
        $SSO["org"],
        $SSO["firstname"],
        $SSO["lastname"],
        $SSO["mail"]
    );

    // define user object
    $USER = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER);
    $_SESSION["is_admin"] = $USER->isAdmin();

    if (isset($_SESSION["viewUser"]) && $_SESSION["is_admin"]) {
        $USER = new UnityUser($_SESSION["viewUser"], $LDAP, $SQL, $MAILER);
    }

    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
}
