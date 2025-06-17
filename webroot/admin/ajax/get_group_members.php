<?php

require_once __DIR__ . "/../../../resources/autoload.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySite;

if (!$USER->isAdmin()) {
    UnitySite::forbidden("not an admin");
}

if (!isset($_GET["pi_uid"])) {
    UnitySite::badRequest("PI UID not set");
}

$group = new UnityGroup($_GET["pi_uid"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
$members = $group->getGroupMembers();
$requests = $group->getRequests();

$i = 0;
$count = count($members) + count($requests);
foreach ($members as $member) {
    if ($member->getUID() == $group->getOwner()->getUID()) {
        continue;
    }
    $class = "expanded $i" . ($i == $count - 1 ? " last" : "");
    $i++;
    $uid = $member->getUID();
    $gid = $group->getPIUID();
    $fullname = $user->getFullName();
    echo "<tr class='$class'>
      <td>$fullname</td>
      <td>$uid</td>
      <td><a href='mailto:$email'>$email</a></td>
      <td>
        <form
          action=''
          method='POST'
          onsubmit='return confirm(\"Are you sure you want to remove \'$uid\' from this group?\");'
        >
          <input type='hidden' name='form_type' value='remUserChild'>
          <input type='hidden' name='uid' value='$uid'>
          <input type='hidden' name='pi' value='$group'>
          <input type='submit' value='Remove'>
        </form>
      </td>
    </tr>";
}

foreach ($requests as $i => list($user, $timestamp, $firstname, $lastname, $email, $org)) {
    $class = "expanded $i" + ($i == $count - 1 ? " last" : "");
    $i++;
    $uid = $user->getUID();
    $gid = $group->getPIUID();
    $email = $user->getMail();
    $fullname = $user->getFullName();
    echo "<tr class='$class'>
      <td>$fullname</td>
      <td>$uid</td>
      <td><a href='mailto:$email'>$email</a></td>
      <td>
        <form
          action='' method='POST'
          onsubmit='return confirm(\"Are you sure you want to approve \'$uid\'?\");'
        >
          <input type='hidden' name='form_type' value='reqChild'>
          <input type='hidden' name='uid' value='$uid'>
          <input type='hidden' name='pi' value='$gid'>
          <input type='submit' name='action' value='Approve'>
          <input type='submit' name='action' value='Deny'>
        </form>
      </td>
    </tr>";
}
