<?php

use UnityWebPortal\lib\UnitySite;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockBuilder;

class ViewAsUserTest extends TestCase
{
    public function testViewAsUser()
    {
        global $USER, $CONFIG, $SITE;
        switchUser(...getAdminUser());
        $this->assertEquals("user1_org1_test", $USER->getUID());
        $this->assertTrue($USER->isAdmin());
        $adminUid = $USER->getUID();
        $oldSite = $SITE;
        try {
            $SITE = $this->createMock(UnitySite::class);
            $SITE->method("redirect");
            post(
                __DIR__ . "/../../webroot/admin/user-mgmt.php",
                [
                    "form_name" => "viewAsUser",
                    "uid" => "foobar",
                ],
            );
            $this->assertArrayHasKey("viewUser", $_SESSION);
            // redirect means that php process dies and user's browser will initiate a new one
            // this makes `require_once autoload.php` run again and init.php changes $USER
            session_write_close();
            get(__DIR__ . "/../../resources/init.php");
            $SITE = $this->createMock(UnitySite::class);
            $SITE->method("redirect");
            // now we should be new user
            $this->assertEquals("foobar", $USER->getUID());
            post(
                __DIR__ . "/../../resources/templates/header.php",
                ["form_name" => "clearView"],
            );
            // redirect means that php process dies and user's browser will initiate a new one
            // this makes `require_once autoload.php` run again and init.php changes $USER
            session_write_close();
            get(__DIR__ . "/../../resources/init.php");
            // now we should be back to original user
            $this->assertEquals($adminUid, $USER->getUID());
        } finally {
            $SITE = $oldSite;
        }
    }
}
