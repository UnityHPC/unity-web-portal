<?php

if (isset($_SERVER["REMOTE_USER"])) {  // Check if SSO is enabled on this page
    // Set SSO Session Vars - Vars stored in session to be accessible outside shib-controlled areas of the sites (ie contact page)
    $SSO = array(
        "user" => EPPN_to_uid($_SERVER["REMOTE_USER"]),
        "firstname" => $_SERVER["givenName"],
        "lastname" => $_SERVER["sn"],
        "name" => $_SERVER["givenName"] . " " . $_SERVER["sn"],
        "mail" => isset($_SERVER["mail"]) ? $_SERVER["mail"] : $_SERVER["eppn"]  // Fallback to EPPN if mail is not set
    );
    $_SESSION["SSO"] = $SSO;  // Set the session var for non-authenticated pages

    // define user object
    $USER = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER);
    $_SESSION["user_exists"] = $USER->exists();
    $_SESSION["is_pi"] = $USER->isPI();
    $_SESSION["is_admin"] = $USER->isAdmin();
}
