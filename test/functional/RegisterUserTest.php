<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityOrg;

class RegisterUserTest extends UnityWebPortalTestCase
{
    public static function provider()
    {
        return [
            // defaults/config.ini.default: ldap.offset_user_(uid|gid)number=1000000
            // test/custom_user_mappings/test.csv has reservations for 1000000-1000004
            ["NonExistent", "1000005", "1000005"],
            // test/custom_user_mappings/test.csv: {user2001: [555, 556]}
            ["CustomMapped555", "555", "556"],
        ];
    }

    private function register()
    {
        http_post(__DIR__ . "/../../webroot/panel/new_account.php", ["eula" => "agree"]);
    }

    #[DataProvider("provider")]
    public function testRegisterUserAndCreateOrg(
        string $nickname,
        string $expected_uidnumber,
        string $expected_gidnumber,
    ): void {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser($nickname);
        $uid = $USER->uid;
        $org_gid = $SSO["org"];
        $user_entry = $LDAP->getUserEntry($uid);
        $user_group_entry = $LDAP->getGroupEntry($uid);
        $org_entry = $LDAP->getOrgGroupEntry($org_gid);
        $this->assertFalse($user_entry->exists());
        $this->assertFalse($user_group_entry->exists());
        $this->assertFalse($org_entry->exists());
        try {
            $this->register();
            $this->assertTrue($user_entry->exists());
            $this->assertTrue($user_group_entry->exists());
            $this->assertTrue($org_entry->exists());
            $this->assertEquals($expected_uidnumber, $user_entry->getAttribute("uidnumber")[0]);
            $this->assertEquals(
                $expected_gidnumber,
                $user_group_entry->getAttribute("gidnumber")[0],
            );
        } finally {
            ensureOrgGroupDoesNotExist($org_gid);
            ensureUserDoesNotExist($uid);
        }
    }
}
