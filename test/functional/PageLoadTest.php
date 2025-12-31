<?php

use PHPUnit\Framework\Attributes\DataProvider;

class PageLoadTest extends UnityWebPortalTestCase
{
    public static function provider()
    {
        return [
            ["Admin", __DIR__ . "/../../webroot/admin/pi-mgmt.php"],
            ["Admin", __DIR__ . "/../../webroot/admin/user-mgmt.php"],
            ["Admin", __DIR__ . "/../../webroot/admin/content.php"],
            ["Admin", __DIR__ . "/../../webroot/admin/notices.php"],
            ["NonExistent", __DIR__ . "/../../webroot/panel/new_account.php"],
            ["Normal", __DIR__ . "/../../webroot/panel/account.php"],
            ["Normal", __DIR__ . "/../../webroot/panel/groups.php"],
            ["Normal", __DIR__ . "/../../webroot/panel/support.php"],
            ["IsPIHasNoMembersNoMemberRequests", __DIR__ . "/../../webroot/panel/pi.php"],
        ];
    }

    #[DataProvider("provider")]
    public function testLoadPage($nickname, $path)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser($nickname);
        http_get($path);
        $this->assertTrue(true); // assert there were no errors
    }
}
