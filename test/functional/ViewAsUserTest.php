<?php

use UnityWebPortal\lib\UnitySite;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ViewAsUserTest extends TestCase
{
    public function testViewAsUser()
    {
        global $USER;
        switchUser(...getAdminUser());
        $this->assertTrue($USER->isAdmin());
        $originalUid = $USER->getUID();
        try {
            post(
                __DIR__ . "/../../webroot/admin/user-mgmt.php",
                [
                    "form_name" => "viewAsUser",
                    "uid" => "foobar",
                ],
            );
        } catch (PhpUnitNoDieException) {}
        $this->assertArrayHasKey("viewUser", $_SESSION);
        // redirect means that php process dies and user's browser will initiate a new one
        // this makes `require_once autoload.php` run again and init.php changes $USER
        session_write_close();
        get(__DIR__ . "/../../resources/init.php");
        // now we should be new user
        $this->assertEquals("foobar", $USER->getUID());
        try {
            post(
                __DIR__ . "/../../resources/templates/header.php",
                ["form_name" => "clearView"],
            );
        } catch (PhpUnitNoDieException) {}
        // redirect means that php process dies and user's browser will initiate a new one
        // this makes `require_once autoload.php` run again and init.php changes $USER
        session_write_close();
        get(__DIR__ . "/../../resources/init.php");
        // now we should be back to original user
        $this->assertEquals($originalUid, $USER->getUID());
    }
}
