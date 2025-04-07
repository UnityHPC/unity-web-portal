<?php

$_SERVER = [
    "REMOTE_ADDR" => "127.0.0.1"
];

global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
require "../../resources/autoload.php";

// ini_set("assert.exception", false);
// ini_set("assert.warning", true);

// ini_set("error_log", "/dev/null");

global $HTTP_HEADER_TEST_INPUTS;
// in theory
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

function switch_to_user(string $eppn, string $given_name, string $sn, string $mail): void {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
    unset($SSO);
    // unset($_SESSION);
    session_write_close();
    session_id(str_replace(["_", "@", "."], "-", $eppn));
    // init.php will call session_start()
    $_SERVER["REMOTE_USER"] = $eppn;
    $_SERVER["eppn"] = $eppn;
    $_SERVER["givenName"] = $given_name;
    $_SERVER["sn"] = $sn;
    ob_start();
    require "../../resources/autoload.php";
    ob_get_clean();
}

function post(string $phpfile, array $post_data): void {
    global $CONFIG, $REDIS, $LDAP, $SQL, $MAILER, $WEBHOOK, $SITE, $SSO, $OPERATOR, $USER, $SEND_PIMESG_TO_ADMINS, $LOC_HEADER, $LOC_FOOTER;
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST = $post_data;
    ob_start();
    try {
        include $phpfile;
    } finally {
        unset($_POST);
        unset($_SERVER["REQUEST_METHOD"]);
        ob_get_clean();
    }
}

switch_to_user(getenv("REMOTE_USER"), "foo", "bar", getenv("REMOTE_USER"));
assert($USER->exists());
