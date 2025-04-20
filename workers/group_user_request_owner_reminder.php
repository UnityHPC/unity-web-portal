<?php

/**
Emails PIs that have oustanding member requests once a week for 4 weeks.
Removes the request after 34 days have passed.
*/

require_once "../resources/autoload.php";
use UnityWebPortal\lib\UnityGroup;

$today = time();
$accounts = $LDAP->getAllPIGroups($SQL, $MAILER, $WEBHOOK);
foreach ($accounts as $pi_group) {
    $pi_user = $pi_group->getOwner();
    $requests = $pi_group->getRequests();
    foreach ($requests as $request) {
        $request_date = strtotime($request[1]);
        $daysDifference = ($today - $request_date) / (60 * 60 * 24);
        if ($daysDifference > 34) {
            // No interface in UnityGroup for this, so use DB directly
            $SQL->removeRequest($request[0]->getUID(), $pi_group->getPIUID());
        } elseif ($daysDifference > 1 && $daysDifference % 7 == 0) {
            $new_user = $request[0];
            // send email to PI
            $MAILER->sendMail(
                $pi_user->getMail(),
                "group_user_request_owner",
                array(
                    "group" => $pi_group->getPIUID(),
                    "user" => $new_user->getUID(),
                    "name" => $new_user->getFullName(),
                    "email" => $new_user->getMail(),
                    "org" => $new_user->getOrg()
                    )
            );
        }
    }
}
