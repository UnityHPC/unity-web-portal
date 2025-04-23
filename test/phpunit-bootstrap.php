<?php

require_once __DIR__ . "/../vendor/autoload.php";

require_once __DIR__ . "/../resources/lib/UnityLDAP.php";
require_once __DIR__ . "/../resources/lib/UnityUser.php";
require_once __DIR__ . "/../resources/lib/UnityGroup.php";
require_once __DIR__ . "/../resources/lib/UnityOrg.php";
require_once __DIR__ . "/../resources/lib/UnitySQL.php";
require_once __DIR__ . "/../resources/lib/UnityMailer.php";
require_once __DIR__ . "/../resources/lib/UnitySSO.php";
require_once __DIR__ . "/../resources/lib/UnitySite.php";
require_once __DIR__ . "/../resources/lib/UnityConfig.php";
require_once __DIR__ . "/../resources/lib/UnityWebhook.php";
require_once __DIR__ . "/../resources/lib/UnityRedis.php";
require_once __DIR__ . "/../resources/lib/UnityGithub.php";
require_once __DIR__ . "/../resources/lib/exceptions/RedirectException.php";

global $HTTP_HEADER_TEST_INPUTS;
$HTTP_HEADER_TEST_INPUTS = [
    '',
    'a',
    'Hello, World!',
    '  Some text  ',
    '   ',
    '12345',
    'abc123',
    'Hello@World!',
    str_repeat('a', 8190), // https://httpd.apache.org/docs/2.2/mod/core.html#limitrequestfieldsize
    '<p>This is a paragraph</p>',
    "'; DROP TABLE users; --",
    "<script>alert('XSS');</script>",
    'こんにちは世界',
    "Hello 👋 World 🌍",
    "Line 1\nLine 2",
    "Column1\tColumn2",
    'MiXeD cAsE',
    'https://www.example.com',
    'user@example.com',
    '{"key": "value"}',
    'SGVsbG8sIFdvcmxkIQ==',
    "Hello\x00World",
    mb_convert_encoding("Hello, World!", "UTF-16")
];

function switchUser(string $eppn, string $given_name, string $sn, string $mail): void
{
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $GITHUB, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
    session_write_close();
    session_id(str_replace(["_", "@", "."], "-", $eppn));
    // session_start will be called on the first post()
    $_SERVER["REMOTE_USER"] = $eppn;
    $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
    $_SERVER["eppn"] = $eppn;
    $_SERVER["givenName"] = $given_name;
    $_SERVER["sn"] = $sn;
    include __DIR__ .  "/../resources/autoload.php";
    assert(!is_null($USER));
}

function post(string $phpfile, array $post_data): void
{
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $GITHUB, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST = $post_data;
    ob_start();
    try {
        include $phpfile;
        ob_get_clean(); // discard output
    } catch (Throwable $e) {
        $output = ob_get_clean();
        if (!empty($output)) {
            error_log($output);
        }
        throw $e;
    } finally {
        unset($_POST);
        unset($_SERVER["REQUEST_METHOD"]);
    }
}

function getNormalUser()
{
    return ["user1@org1.test", "foo", "bar", "user1@org1.test"];
}

function getNormalUser2()
{
    return ["user2@org1.test", "foo", "bar", "user2@org1.test"];
}

function getUserHasNotRequestedAccountDeletionHasGroup()
{
    return ["user1@org1.test", "foo", "bar", "user1@org1.test"];
}

function getUserHasNotRequestedAccountDeletionHasNoGroups()
{
    return ["user2@org1.test", "foo", "bar", "user2@org1.test"];
}

function getUserHasNoSshKeys()
{
    return ["user3@org1.test", "foo", "bar", "user3@org1.test"];
}

function getUserNotPiNotRequestedBecomePi()
{
    return ["user2@org1.test", "foo", "bar", "user2@org1.test"];
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
