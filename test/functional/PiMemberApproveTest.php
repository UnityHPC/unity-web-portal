<?php

use UnityWebPortal\lib\UserFlag;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use TRegx\PhpUnit\DataProviders\DataProvider as TRegxDataProvider;

class PIMemberApproveTest extends UnityWebPortalTestCase
{
    // two different ways to accept a user into a PI group
    public static function approveUserProvider(): TRegxDataProvider
    {
        return TRegxDataProvider::list(
            function ($uid, $gid) {
                global $USER;
                assert($USER->getPIGroup()->gid === $gid, "signed in user must be the group owner");
                http_post(__DIR__ . "/../../webroot/panel/pi.php", [
                    "form_type" => "userReq",
                    "action" => "Approve",
                    "uid" => $uid,
                ]);
            },
            function ($uid, $gid) {
                $this->switchUser("Admin");
                try {
                    http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
                        "form_type" => "reqChild",
                        "action" => "Approve",
                        "pi" => $gid,
                        "uid" => $uid,
                    ]);
                } finally {
                    $this->switchBackUser();
                }
            },
        );
    }

    #[DataProvider("approveUserProvider")]
    public function testApproveNonexistentRequest($approveUserFunc)
    {
        global $USER;
        $this->switchUser("Blank");
        $uid = $USER->uid;
        $this->switchUser("EmptyPIGroupOwner");
        $piGroup = $USER->getPIGroup();
        try {
            $this->expectException(Exception::class); // FIXME more specific exception type
            $approveUserFunc($uid, $piGroup->gid);
        } finally {
            $this->switchUser("Blank", validate: false);
            ensureUserNotInPIGroup($piGroup);
        }
    }

    #[DataProvider("approveUserProvider")]
    public function testApproveMember($func)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_uid = $USER->uid;
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        $this->switchUser("Blank");
        try {
            $pi_group->newUserRequest($USER);
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $approve_uid = $SSO["user"];
            $this->switchUser("EmptyPIGroupOwner", validate: false);
            $func($approve_uid, $gid);
            $this->switchUser("Blank", validate: false);

            $this->assertFalse($pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->memberUIDExists($USER->uid));
            $this->assertTrue($USER->getFlag(UserFlag::QUALIFIED));
        } finally {
            $this->switchUser("Blank", validate: false);
            ensureUserNotInPIGroup($pi_group);
            $this->assertGroupMembers($pi_group, [$pi_uid]);
            $SQL->removeRequest($USER->uid, $gid);
        }
    }
}
