<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityHTTPDMessageLevel;

class DeleteMessageTest extends TestCase
{
    public function testDeleteMessage(): void
    {
        switchUser(...getBlankUser());
        $initial = UnityHTTPD::getMessages();
        $this->assertEmpty($initial);
        UnityHTTPD::messageDebug("foo1", "bar1");
        UnityHTTPD::messageDebug("foo2", "bar2");
        UnityHTTPD::messageDebug("foo3", "bar3");
        UnityHTTPD::messageError("foo", "bar");
        UnityHTTPD::messageInfo("foo", "bar");
        UnityHTTPD::messageSuccess("foo", "bar");
        UnityHTTPD::messageWarning("foo", "bar");
        try {
            $before = array_map("jsonEncode", UnityHTTPD::getMessages());
            http_post(
                __DIR__ . "/../../webroot/panel/ajax/delete_message.php",
                [
                    "level" => base64_encode("debug"),
                    "title" => base64_encode("foo2"),
                    "body" => base64_encode("bar2"),
                ],
                enforce_PRG: false,
            );
            $after = array_map("jsonEncode", UnityHTTPD::getMessages());
            $difference = array_diff($before, $after);
            $message_expected_removed = ["foo2", "bar2", UnityHTTPDMessageLevel::DEBUG];
            $this->assertEqualsCanonicalizing([jsonEncode($message_expected_removed)], $difference);
        } finally {
            UnityHTTPD::clearMessages();
        }
    }
}
