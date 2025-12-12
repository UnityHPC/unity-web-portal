<?php

use PHPUnit\Framework\Attributes\DataProvider;

class PageLoadTest extends UnityWebPortalTestCase
{
    public static function provider()
    {
        $admin = getAdminUser();
        $nonexistent_user = getNonExistentUser();
        $normal_user = getNormalUser();
        $pi = getUserIsPIHasNoMembersNoMemberRequests();
        return [
            [$admin, __DIR__ . "/../../webroot/admin/pi-mgmt.php"],
            [$admin, __DIR__ . "/../../webroot/admin/user-mgmt.php"],
            [$admin, __DIR__ . "/../../webroot/admin/content.php"],
            [$admin, __DIR__ . "/../../webroot/admin/notices.php"],
            [$nonexistent_user, __DIR__ . "/../../webroot/panel/new_account.php"],
            [$normal_user, __DIR__ . "/../../webroot/panel/account.php"],
            [$normal_user, __DIR__ . "/../../webroot/panel/groups.php"],
            [$normal_user, __DIR__ . "/../../webroot/panel/support.php"],
            [$pi, __DIR__ . "/../../webroot/panel/pi.php"],
        ];
    }

    #[DataProvider("provider")]
    public function testLoadPage($user, $path)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        switchuser(...$user);
        http_get($path);
        $this->assertTrue(true); // assert there were no errors
    }
}
