#!/usr/bin/env php
<?php
$_SERVER["HTTP_HOST"] = "course-creator"; // see deployment/overrides/course-creator
include __DIR__ . "/init.php";
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityOrg;
use UnityWebPortal\lib\UserFlag;

// if array is length 1 then replace it with its one element
function flatten_attributes(array $attributes): array
{
    return array_map(fn($v) => count($v) === 1 ? $v[0] : $v, $attributes);
}

/** return string[] */
function parse_comma_delimited_list(string $input): array
{
    $output = trim($input);
    $output = explode(",", $output);
    $output = array_map("trim", $output);
    $output = array_unique($output);
    $output = array_filter($output, fn($x) => $x !== "");
    return $output;
}

$givenName = trim(readline("Enter the course ID (example: CS123): "));
if (!_preg_match("/^[a-zA-Z0-9_-]+$/", $givenName)) {
    _die("error: course ID '$givenName' contains invalid characters", 1);
}
$sn = trim(readline("Enter the year and semester of the course (example: Fall 2025): "));
$org_gid = strtolower(trim(readline("Please enter the organization (example: umass_edu): ")));
if (!_preg_match("/^[a-z0-9_]+$/", $org_gid)) {
    _die("error: organization '$org_gid' contains invalid characters", 1);
}
$cn = implode("_", [strtolower($givenName), $org_gid]);
$manager_uids = parse_comma_delimited_list(
    readline("Enter the UID(s) of the group manager(s) (example: simonleary_umass_edu,bryank_uri_edu): ")
);
if (count($manager_uids) === 0) {
    _die("at least one group manager UID is required", 1);
}

$managers = [];
foreach ($manager_uids as $manager_uid) {
    array_push($managers, new UnityUser($manager_uid, $LDAP, $SQL, $MAILER));
    if (!end($managers)->exists()) {
        _die("no such user: '$manager_uid'", 1);
    }
}

$course_user = new UnityUser($cn, $LDAP, $SQL, $MAILER);
if ($course_user->exists()) {
    $course_user_dn = $LDAP->getUserEntry($cn)->getDN();
    _die("course user already exists: '$course_user_dn'", 1);
}
$org = new UnityOrg($org_gid, $LDAP);
if (!$org->exists()) {
    print "WARNING: creating new org '$org_gid'...\n";
}
$mail = ""; // temporary empty mail
$course_user->init($givenName, $sn, $mail, $org_gid);
$course_user->setFlag(UserFlag::IMMORTAL, true, false, true);

$course_pi_group = $course_user->getPIGroup();
$course_user->setMail($course_pi_group->addPlusAddressToMail($managers[0]->getMail()));

if ($course_pi_group->exists()) {
    $course_pi_group_dn = $LDAP->getPIGroupEntry($course_pi_group->gid)->getDN();
    _die("course PI group already exists: '$course_pi_group_dn'", 1);
}
$course_pi_group->requestGroup(false, false);
$course_pi_group->approveGroup();

foreach ($managers as $manager) {
    $course_pi_group->newUserRequest($manager, false);
    $course_pi_group->approveUser($manager);
    $course_pi_group->addManagerUID($manager->uid);
}

print "LDAP entries created:\n";
print _json_encode(
    [
        "course user" => flatten_attributes($LDAP->getUserEntry($cn)->getAttributes()),
        "course user group" => flatten_attributes($LDAP->getUserGroupEntry($cn)->getAttributes()),
        "course PI group" => flatten_attributes(
            $LDAP->getPIGroupEntry($course_pi_group->gid)->getAttributes(),
        ),
    ],
    JSON_PRETTY_PRINT,
);

