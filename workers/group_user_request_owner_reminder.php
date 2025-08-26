<?php

/**
Emails PIs that have oustanding member requests once a week for 4 weeks.
Removes the request after 34 days have passed.
*/

require_once __DIR__ . "/../resources/autoload.php";
use UnityWebPortal\lib\UnityGroup;

$today = time();
$accounts = $LDAP->getAllPIGroups($SQL, $MAILER, $REDIS, $WEBHOOK);
foreach ($accounts as $pi_group) {
    $pi_user = $pi_group->getOwner();
    $requests = $pi_group->getRequests();
    foreach ($requests as [$uid, $timestamp, $firstname, $lastname, $email, $org]) {
        $request_date = strtotime($request[1]);
        $daysDifference = ($today - $request_date) / (60 * 60 * 24);
        if ($daysDifference > 34) {
            // No interface in UnityGroup for this, so use DB directly
            $SQL->removeRequest($uid, $pi_group->gid);
        } elseif ($daysDifference > 1 && $daysDifference % 7 == 0) {
            // send email to PI
            $MAILER->sendMail(
                $pi_user->getMail(),
                "group_user_request_owner",
                array(
                    "group" => $pi_group->gid,
                    "user" => $uid,
                    "name" => $firstname . " " . $lastname,
                    "email" => $email,
                    "org" => $org,
                )
            );
        }
    }
}
