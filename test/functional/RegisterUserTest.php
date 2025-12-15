<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityOrg;
use PHPOpenLDAPer\LDAPEntry;

class RegisterUserTest extends UnityWebPortalTestCase
{
    public static function provider()
    {
        return [
            getNonExistentUserAndExpectedUIDGIDNoCustomMapping(),
            getNonExistentUserAndExpectedUIDGIDWithCustomMapping(),
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
        switchuser(...$user_to_register_args);
        $user_entry = new LDAPEntry($LDAP, $LDAP->getUserDN($USER->uid));
        $user_group_entry = new LDAPEntry($LDAP, $LDAP->getUserGroupDN($USER->uid));
        $org_entry = new LDAPEntry($LDAP, $LDAP->getOrgGroupDN($SSO["org"]));
        $this->assertTrue(!$user_entry->exists());
        $this->assertTrue(!$user_group_entry->exists());
        $this->assertTrue(!$org_entry->exists());
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
