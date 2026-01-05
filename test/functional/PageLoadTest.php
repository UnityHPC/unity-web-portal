<?php

use PHPUnit\Framework\Attributes\DataProvider;

class PageLoadTest extends UnityWebPortalTestCase
{
    public static function provider()
    {
        return [
            // normal page load
            ["Admin", "admin/pi-mgmt.php", "/PI Management/"],
            ["Admin", "admin/user-mgmt.php", "/User Management/"],
            ["Admin", "admin/content.php", "/Page Content Management/"],
            ["Admin", "admin/notices.php", "/Cluster Notice Management/"],
            ["NonExistent", "panel/new_account.php", "/Register New Account/"],
            ["Blank", "panel/account.php", "/Account Settings/"],
            ["Blank", "panel/groups.php", "/My Principal Investigators/"],
            ["Blank", "panel/support.php", "/Support/"],
            ["EmptyPIGroupOwner", "panel/pi.php", "/My Users/"],
            // normal user should not be able to access admin pages
            ["Blank", "admin/pi-mgmt.php", "/You are not an admin/"],
            ["Blank", "admin/user-mgmt.php", "/You are not an admin/"],
            ["Blank", "admin/content.php", "/You are not an admin/"],
            ["Blank", "admin/notices.php", "/You are not an admin/"],
            // new_account.php should redirect to account.php if account already exists
            ["Blank", "panel/new_account.php", "/panel\/account\.php/"],
            // all pages should redirect to new_account.php if account does not exist
            ["NonExistent", "panel/account.php", "/panel\/new_account\.php/"],
        ];
    }

    #[DataProvider("provider")]
    public function testLoadPage($nickname, $path, $regex)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser($nickname);
        $output = http_get(__DIR__ . "/../../webroot/" . $path);
        $this->assertMatchesRegularExpression($regex, $output);
    }
}
