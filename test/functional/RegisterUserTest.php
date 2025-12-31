<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityOrg;

class RegisterUserTest extends UnityWebPortalTestCase
{
    public static function provider()
    {
        return [
            // user2002_org998_test has no custom mapping
            // defaults/config.ini.default: ldap.offset_UIDGID=1000000
            // test/custom_user_mappings/test.csv has reservations for 1000000-1000004
            [["user2002@org998.test", "foo", "bar", "user2002@org998.test"], 1000005],
            // user2001_org998_test has a custom mapping:
            // test/custom_user_mappings/test.csv: {user2001: 555}
            [["user2001@org998.test", "foo", "bar", "user2001@org998.test"], 555],
        ];
    }

    private function register()
    {
        http_post(__DIR__ . "/../../webroot/panel/new_account.php", ["eula" => "agree"]);
    }

    #[DataProvider("provider")]
    public function testRegisterUserAndCreateOrg($user_to_register_args, $expected_uid_gid)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        switchUser(...$user_to_register_args);
        $user_entry = $LDAP->getUserEntry($USER->uid);
        $user_group_entry = $LDAP->getGroupEntry($USER->uid);
        $org_entry = $LDAP->getOrgGroupEntry($SSO["org"]);
        $this->assertFalse($user_entry->exists());
        $this->assertFalse($user_group_entry->exists());
        $this->assertFalse($org_entry->exists());
        try {
            $this->register();
            $this->assertTrue($user_entry->exists());
            $this->assertTrue($user_group_entry->exists());
            $this->assertTrue($org_entry->exists());
            $this->assertEquals($expected_uid_gid, $user_entry->getAttribute("uidnumber")[0]);
            $this->assertEquals($expected_uid_gid, $user_group_entry->getAttribute("gidnumber")[0]);
        } finally {
            ensureOrgGroupDoesNotExist();
            ensureUserDoesNotExist();
        }
    }
}
