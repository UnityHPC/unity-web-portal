<?php

use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;
use PHPUnit\Framework\TestCase;

class ViewAsUserTest extends TestCase
{
    public function _testViewAsUser(array $beforeUser, array $afterUser)
    {
        global $USER;
        switchUser(...$afterUser);
        $afterUid = $USER->getUID();
        switchUser(...$beforeUser);
        // $this->assertTrue($USER->isAdmin());
        $beforeUid = $USER->getUID();
        // $this->assertNotEquals($afterUid, $beforeUid);
        http_post(
            __DIR__ . "/../../webroot/admin/user-mgmt.php",
            [
                "form_type" => "viewAsUser",
                "uid" => $afterUid,
            ],
        );
        $this->assertArrayHasKey("viewUser", $_SESSION);
        // redirect means that php process dies and user's browser will initiate a new one
        // this makes `require_once autoload.php` run again and init.php changes $USER
        session_write_close();
        http_get(__DIR__ . "/../../resources/init.php");
        // now we should be new user
        $this->assertEquals($afterUid, $USER->getUID());
        // $this->assertTrue($_SESSION["user_exists"]);
        http_post(
            __DIR__ . "/../../resources/templates/header.php",
            ["form_type" => "clearView"],
        );
        $this->assertArrayNotHasKey("viewUser", $_SESSION);
        // redirect means that php process dies and user's browser will initiate a new one
        // this makes `require_once autoload.php` run again and init.php changes $USER
        session_write_close();
        http_get(__DIR__ . "/../../resources/init.php");
        // now we should be back to original user
        $this->assertEquals($beforeUid, $USER->getUID());
    }

    public function testViewAsUser()
    {
        $this->_testViewAsUser(getAdminUser(), getNormalUser());
    }

    public function testViewAsNonExistentUser()
    {
        $this->_testViewAsUser(getAdminUser(), getNonExistentUser());
    }

    public function testViewAsSelf()
    {
        $this->_testViewAsUser(getAdminUser(), getAdminUser());
    }

    public function testNonAdminViewAsAdmin()
    {
        global $USER;
        switchUser(...getAdminUser());
        $adminUid = $USER->getUID();
        $this->assertTrue($USER->isAdmin());
        switchUser(...getNormalUser());
        http_post(
            __DIR__ . "/../../webroot/admin/user-mgmt.php",
            [
                "form_type" => "viewAsUser",
                "uid" => $adminUid,
            ],
        );
        $this->assertArrayNotHasKey("viewUser", $_SESSION);
    }
}
