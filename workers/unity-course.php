#!/usr/bin/env php
<?php
$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; // needed for audit log
$_SERVER["HTTP_HOST"] = "course-creator"; // see deployment/overrides/course-creator

require_once __DIR__ . "/../resources/autoload.php";
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityOrg;

function cn2org($cn)
{
    $matches = [];
    preg_match("/.*_([^_]+_[^_]+)$/", $cn, $matches);
    ensure(count($matches) == 2, "failed to extract org from cn: '$cn'");
    return $matches[1];
}

function insert_plus_address($email, $plus)
{
    $parts = explode("@", $email, 2);
    return $parts[0] . "+" . $plus . "@" . $parts[1];
}

// if array is length 1 then replace it with its one element
function flatten_attributes(array $attributes): array
{
    return array_map(fn($v) => count($v) === 1 ? $v[0] : $v, $attributes);
}

$givenName = trim(readline("Enter the course ID (example: CS123): "));
$sn = trim(readline("Enter the year and semester of the course (example: Fall 2025): "));
$cn = strtolower(
    trim(readline("Please enter the cn to be used for the course (example: cs123_umass_edu): ")),
);
$operator_uid = trim(
    readline(
        "Enter the UID of the Unity team member responsible for the course (example: simonleary_umass_edu): ",
    ),
);
$org_gid = cn2org($cn);

$operator = new UnityUser($operator_uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$operator->exists()) {
    die("no such user: '$operator_uid'");
}

$course_user = new UnityUser($cn, $LDAP, $SQL, $MAILER, $WEBHOOK);
if ($course_user->exists()) {
    $course_user_dn = $LDAP->getUserEntry($cn)->getDN();
    die("course user already exists: '$course_user_dn'");
}
$org = new UnityOrg($org_gid, $LDAP, $SQL, $MAILER, $WEBHOOK);
if (!$org->exists()) {
    print "WARNING: creating new org '$org_gid'...\n";
}
$mail = insert_plus_address($operator->getMail(), $cn);
$course_user->init($givenName, $sn, $mail, $org_gid);

$course_pi_group = $course_user->getPIGroup();
if ($course_pi_group->exists()) {
    $course_pi_group_dn = $LDAP->getPIGroupEntry($course_pi_group->gid)->getDN();
    die("course PI group already exists: '$course_pi_group_dn'");
}
$course_pi_group->requestGroup(false, false);
$course_pi_group->approveGroup();
print "LDAP entries created:\n";
print jsonEncode(
    [
        "course user" => flatten_attributes($LDAP->getUserEntry($cn)->getAttributes()),
        "course user group" => flatten_attributes($LDAP->getGroupEntry($cn)->getAttributes()),
        "course PI group" => flatten_attributes(
            $LDAP->getPIGroupEntry($course_pi_group->gid)->getAttributes(),
        ),
    ],
    JSON_PRETTY_PRINT,
);

$course_pi_group->newUserRequest($operator, false);
$course_pi_group->approveUser($operator);

