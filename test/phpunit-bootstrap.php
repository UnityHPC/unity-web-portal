<?php
// submodule
require_once __DIR__ . "/../resources/lib/phpopenldaper/src/PHPOpenLDAPer/LDAPEntry.php";
require_once __DIR__ . "/../resources/lib/phpopenldaper/src/PHPOpenLDAPer/LDAPConn.php";

require_once __DIR__ . "/../resources/lib/UnityLDAP.php";
require_once __DIR__ . "/../resources/lib/UnityUser.php";
require_once __DIR__ . "/../resources/lib/PosixGroup.php";
require_once __DIR__ . "/../resources/lib/UnityGroup.php";
require_once __DIR__ . "/../resources/lib/UnityOrg.php";
require_once __DIR__ . "/../resources/lib/UnitySQL.php";
require_once __DIR__ . "/../resources/lib/UnityMailer.php";
require_once __DIR__ . "/../resources/lib/UnitySSO.php";
require_once __DIR__ . "/../resources/lib/UnityHTTPD.php";
require_once __DIR__ . "/../resources/lib/UnityConfig.php";
require_once __DIR__ . "/../resources/lib/UnityWebhook.php";
require_once __DIR__ . "/../resources/lib/UnityGithub.php";
require_once __DIR__ . "/../resources/lib/utils.php";
require_once __DIR__ . "/../resources/lib/exceptions/NoDieException.php";
require_once __DIR__ . "/../resources/lib/exceptions/SSOException.php";
require_once __DIR__ . "/../resources/lib/exceptions/ArrayKeyException.php";
require_once __DIR__ . "/../resources/lib/exceptions/CurlException.php";
require_once __DIR__ . "/../resources/lib/exceptions/EntryNotFoundException.php";
require_once __DIR__ . "/../resources/lib/exceptions/EnsureException.php";
require_once __DIR__ . "/../resources/lib/exceptions/EncodingUnknownException.php";
require_once __DIR__ . "/../resources/lib/exceptions/EncodingConversionException.php";
require_once __DIR__ . "/../resources/lib/exceptions/UnityHTTPDMessageNotFoundException.php";

use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnityHTTPDMessageLevel;
use PHPUnit\Framework\TestCase;

$_SERVER["HTTP_HOST"] = "phpunit"; // used for config override
require_once __DIR__ . "/../resources/config.php";

global $HTTP_HEADER_TEST_INPUTS;
$HTTP_HEADER_TEST_INPUTS = [
    "",
    "a",
    "Hello, World!",
    "  Some text  ",
    "   ",
    "12345",
    "abc123",
    "Hello@World!",
    str_repeat("a", 8190), // https://httpd.apache.org/docs/2.2/mod/core.html#limitrequestfieldsize
    "<p>This is a paragraph</p>",
    "'; DROP TABLE users; --",
    "<script>alert('XSS');</script>",
    "こんにちは世界",
    "Hello 👋 World 🌍",
    "Line 1\nLine 2",
    "Column1\tColumn2",
    "MiXeD cAsE",
    "https://www.example.com",
    "user@example.com",
    '{"key": "value"}',
    "SGVsbG8sIFdvcmxkIQ==",
    "Hello\x00World",
    mbConvertEncoding("Hello, World!", "UTF-16"),
];

function switchUser(
    string $eppn,
    string $given_name,
    string $sn,
    string $mail,
    ?string $session_id = null,
): void {
    global $LDAP,
        $SQL,
        $MAILER,
        $WEBHOOK,
        $GITHUB,
        $SITE,
        $SSO,
        $OPERATOR,
        $USER,
        $SEND_PIMESG_TO_ADMINS,
        $LOC_HEADER,
        $LOC_FOOTER;
    session_write_close();
    if (is_null($session_id)) {
        session_id(str_replace(["_", "@", "."], "-", uniqid($eppn . "_")));
    } else {
        session_id($session_id);
    }
    // session_start will be called on the first post()
    $_SERVER["REMOTE_USER"] = $eppn;
    $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
    $_SERVER["HTTP_HOST"] = "phpunit"; // used for config override
    $_SERVER["eppn"] = $eppn;
    $_SERVER["givenName"] = $given_name;
    $_SERVER["sn"] = $sn;
    include __DIR__ . "/../resources/autoload.php";
    ensure(!is_null($USER));
}

function http_post(string $phpfile, array $post_data, bool $enforce_PRG = true): void
{
    global $LDAP,
        $SQL,
        $MAILER,
        $WEBHOOK,
        $GITHUB,
        $SITE,
        $SSO,
        $OPERATOR,
        $USER,
        $SEND_PIMESG_TO_ADMINS,
        $LOC_HEADER,
        $LOC_FOOTER;
    $_PREVIOUS_SERVER = $_SERVER;
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_SERVER["PHP_SELF"] = preg_replace("/.*webroot\//", "/", $phpfile);
    $_SERVER["REQUEST_URI"] = preg_replace("/.*webroot\//", "/", $phpfile); // Slightly imprecise because it doesn't include get parameters
    $_POST = $post_data;
    ob_start();
    $post_did_redirect_or_die = false;
    try {
        include $phpfile;
    } catch (UnityWebPortal\lib\exceptions\NoDieException $e) {
        $post_did_redirect_or_die = true;
    } finally {
        ob_get_clean(); // discard output
        unset($_POST);
        $_SERVER = $_PREVIOUS_SERVER;
    }
    if ($enforce_PRG) {
        // https://en.wikipedia.org/wiki/Post/Redirect/Get
        ensure($post_did_redirect_or_die, "post did not redirect or die!");
    }
}

function http_get(string $phpfile, array $get_data = []): void
{
    global $LDAP,
        $SQL,
        $MAILER,
        $WEBHOOK,
        $GITHUB,
        $SITE,
        $SSO,
        $OPERATOR,
        $USER,
        $SEND_PIMESG_TO_ADMINS,
        $LOC_HEADER,
        $LOC_FOOTER;
    $_PREVIOUS_SERVER = $_SERVER;
    $_SERVER["REQUEST_METHOD"] = "GET";
    $_SERVER["PHP_SELF"] = preg_replace("/.*webroot\//", "/", $phpfile);
    $_SERVER["REQUEST_URI"] = preg_replace("/.*webroot\//", "/", $phpfile); // Slightly imprecise because it doesn't include get parameters
    $_GET = $get_data;
    ob_start();
    try {
        include $phpfile;
    } finally {
        ob_get_clean(); // discard output
        unset($_GET);
        $_PREVIOUS_SERVER = $_SERVER;
    }
}

// delete requests made by that user
// delete user entry
// delete user group entry
// remove user from org group
// remove user from "all users" group
// does not remove user from PI groups
function ensureUserDoesNotExist()
{
    global $USER, $SQL, $LDAP;
    $SQL->deleteRequestsByUser($USER->uid);
    if ($USER->exists()) {
        $org = $USER->getOrgGroup();
        if ($org->exists() and $org->mermberUIDExists($USER->uid)) {
            $org->removeUser($USER);
            ensure(!$org->mermberUIDExists($USER->uid));
        }
        $LDAP->getUserEntry($USER->uid)->delete();
        ensure(!$USER->exists());
    }
    if ($USER->getGroupEntry()->exists()) {
        $USER->getGroupEntry()->delete();
        ensure(!$USER->getGroupEntry()->exists());
    }
    $qualified_users_group = $LDAP->getQualifiedUserGroup();
    $all_member_uids = $qualified_users_group->getAttribute("memberuid");
    if (in_array($USER->uid, $all_member_uids)) {
        $qualified_users_group->setAttribute(
            "memberuid",
            // array_diff will break the contiguity of the array indexes
            // ldap_mod_replace requires contiguity, array_values restores contiguity
            array_values(array_diff($all_member_uids, [$USER->uid])),
        );
        $qualified_users_group->write();
        ensure(!in_array($USER->uid, $qualified_users_group->getAttribute("memberuid")));
    }
}

function ensureOrgGroupDoesNotExist()
{
    global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
    $org_group = $LDAP->getOrgGroupEntry($SSO["org"]);
    if ($org_group->exists()) {
        $org_group->delete();
        ensure(!$org_group->exists());
    }
}

function ensureUserNotRequestedAccountDeletion()
{
    global $USER, $SQL;
    if ($SQL->accDeletionRequestExists($USER->uid)) {
        $SQL->deleteAccountDeletionRequest($USER->uid);
    }
}

function ensureUserNotInPIGroup(UnityGroup $pi_group)
{
    global $USER;
    if ($pi_group->mermberUIDExists($USER->uid)) {
        $pi_group->removeUser($USER);
        ensure(!$pi_group->mermberUIDExists($USER->uid));
    }
}

function ensurePIGroupDoesNotExist()
{
    global $USER, $LDAP;
    $gid = $USER->getPIGroup()->gid;
    if ($USER->getPIGroup()->exists()) {
        $LDAP->getPIGroupEntry($gid)->delete();
        ensure(!$USER->getPIGroup()->exists());
    }
}

function getNormalUser()
{
    return ["user2@org1.test", "foo", "bar", "user2@org1.test"];
}

function getNormalUser2()
{
    return ["user2@org1.test", "foo", "bar", "user2@org1.test"];
}

function getUserHasNotRequestedAccountDeletionHasGroup()
{
    return ["user1@org1.test", "foo", "bar", "user1@org1.test"];
}

/* a blank user has no requests, no PI group, and has not requested account deletion */
function getBlankUser()
{
    return ["user2@org1.test", "foo", "bar", "user2@org1.test"];
}

function getUserHasNoSshKeys()
{
    return ["user3@org1.test", "foo", "bar", "user3@org1.test"];
}

function getUserNotPiNotRequestedBecomePiRequestedAccountDeletion()
{
    return ["user4@org1.test", "foo", "bar", "user4@org1.test"];
}

function getUserWithOneKey()
{
    return ["user5@org2.test", "foo", "bar", "user5@org2.test"];
}

function getUserIsPIHasNoMembersNoMemberRequests()
{
    return ["user5@org2.test", "foo", "bar", "user5@org2.test"];
}

function getUserIsPIHasAtLeastOneMember()
{
    return ["user1@org1.test", "foo", "bar", "user1@org1.test"];
}

function getNonExistentUser()
{
    return ["user2001@org998.test", "foo", "bar", "user2001@org998.test"];
}

function getUnqualifiedUser()
{
    return ["user2005@org1.test", "foo", "bar", "user2005@org1.test"];
}

function getNonexistentUsersWithExistentOrg()
{
    return [
        ["user2003@org1.test", "foo", "bar", "user2003@org1.test"],
        ["user2004@org1.test", "foo", "bar", "user2004@org1.test"],
    ];
}

function getNonExistentUserAndExpectedUIDGIDNoCustomMapping()
{
    // defaults/config.ini.default: ldap.offset_UIDGID=1000000
    // test/custom_user_mappings/test.csv has reservations for 1000000-1000004
    return [["user2002@org998.test", "foo", "bar", "user2002@org998.test"], 1000005];
}

function getNonExistentUserAndExpectedUIDGIDWithCustomMapping()
{
    // test/custom_user_mappings/test.csv: {user2001: 555}
    return [["user2001@org998.test", "foo", "bar", "user2001@org998.test"], 555];
}

function getMultipleValueAttributesAndExpectedSSO()
{
    return [
        [
            "REMOTE_USER" => "user2003@org1.test",
            "givenName" => "foo;foo",
            "sn" => "bar;bar",
            "mail" => "user2003@org1.test;user2003@org1.test",
        ],
        [
            "firstname" => "foo",
            "lastname" => "bar",
            "mail" => "user2003@org1.test",
        ],
    ];
}

function getAdminUser()
{
    return ["user1@org1.test", "foo", "bar", "user1@org1.test"];
}

class UnityWebPortalTestCase extends TestCase
{
    public function assertMessageExists(
        UnityHTTPDMessageLevel $level,
        string $title_regex,
        string $body_regex,
    ) {
        $messages = UnityHTTPD::getMessages();
        $error_msg = sprintf(
            "message(level='%s' title_regex='%s' body_regex='%s'), not found. found messages: %s",
            $level->value,
            $title_regex,
            $body_regex,
            jsonEncode($messages),
        );
        $messages_with_title = array_filter($messages, fn($x) => preg_match($title_regex, $x[0]));
        $messages_with_title_and_body = array_filter(
            $messages_with_title,
            fn($x) => preg_match($body_regex, $x[1]),
        );
        $messages_with_title_and_body_and_level = array_filter(
            $messages_with_title_and_body,
            fn($x) => $x[2] == $level,
        );
        $this->assertNotEmpty($messages_with_title_and_body_and_level, $error_msg);
    }

    public function assertGroupMembers(UnityGroup $group, array $expected_members)
    {
        sort($expected_members);
        $found_members = $group->getMemberUIDs();
        sort($found_members);
        $this->assertEqualsCanonicalizing($expected_members, $found_members);
    }

    public function assertRequestedMembership(bool $expected, string $gid)
    {
        global $USER, $SQL;
        $this->assertEquals($expected, $SQL->requestExists($USER->uid, $gid));
    }

    public function getNumberAccountDeletionRequests()
    {
        global $USER, $SQL;
        $stmt = $SQL->getConn()->prepare("SELECT * FROM account_deletion_requests WHERE uid=:uid");
        $uid = $USER->uid;
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
        return count($stmt->fetchAll());
    }

    public function assertNumberAccountDeletionRequests(int $x)
    {
        global $USER, $SQL;
        if ($x == 0) {
            $this->assertFalse($USER->hasRequestedAccountDeletion());
            $this->assertFalse($SQL->accDeletionRequestExists($USER->uid));
        } elseif ($x > 0) {
            $this->assertTrue($USER->hasRequestedAccountDeletion());
            $this->assertTrue($SQL->accDeletionRequestExists($USER->uid));
        } else {
            throw new RuntimeException("x must not be negative");
        }
        $this->assertEquals($x, $this->getNumberAccountDeletionRequests());
    }

    public function assertRequestedPIGroup(bool $expected)
    {
        global $USER, $SQL;
        $this->assertEquals(
            $expected,
            $SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI),
        );
    }

    public function getNumberPiBecomeRequests()
    {
        global $USER, $SQL;
        // FIXME table name, "admin" are public constants in UnitySQL
        // FIXME "admin" should be something else
        $stmt = $SQL
            ->getConn()
            ->prepare("SELECT * FROM requests WHERE uid=:uid and request_for='admin'");
        $uid = $USER->uid;
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
        return count($stmt->fetchAll());
    }

    public function assertNumberPiBecomeRequests(int $x)
    {
        global $USER, $SQL;
        if ($x == 0) {
            $this->assertFalse($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI));
        } elseif ($x > 0) {
            $this->assertTrue($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI));
        } else {
            throw new RuntimeException("x must not be negative");
        }
        $this->assertEquals($x, $this->getNumberPiBecomeRequests());
    }

    public function getNumberRequests()
    {
        global $USER, $SQL;
        return count($SQL->getRequestsByUser($USER->uid));
    }

    public function assertNumberRequests(int $x)
    {
        $this->assertEquals($x, $this->getNumberRequests());
    }
}
