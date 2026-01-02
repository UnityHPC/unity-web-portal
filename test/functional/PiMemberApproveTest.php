<?php

use UnityWebPortal\lib\UserFlag;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use TRegx\PhpUnit\DataProviders\DataProvider as TRegxDataProvider;

class PIMemberApproveTest extends UnityWebPortalTestCase
{
    private function cancelRequestGroupMembership($gid)
    {
        http_post(__DIR__ . "/../../webroot/panel/groups.php", [
            "form_type" => "cancelPIForm",
            "pi" => $gid,
        ]);
    }

    // two different ways to request to join a group
    public static function requestGroupMembershipProvider(): TRegxDataProvider
    {
        return TRegxDataProvider::list(
            function (UnityUser $requesting_user, UnityGroup $pi_group) {
                global $USER;
                assert($USER === $requesting_user, "signed in user must be the requesting user");
                http_post(__DIR__ . "/../../webroot/panel/groups.php", [
                    "form_type" => "addPIform",
                    "tos" => "agree",
                    "pi" => $pi_group->gid,
                ]);
            },
            function (UnityUser $requesting_user, UnityGroup $pi_group) {
                global $USER;
                assert($USER === $requesting_user, "signed in user must be the requesting user");
                http_post(__DIR__ . "/../../webroot/panel/groups.php", [
                    "form_type" => "addPIform",
                    "tos" => "agree",
                    "pi" => $pi_group->getOwner()->getMail(),
                ]);
            },
        );
    }

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
                http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
                    "form_type" => "reqChild",
                    "action" => "Approve",
                    "pi" => $gid,
                    "uid" => $uid,
                ]);
            },
        );
    }

    public static function requestGroupMembershipAndApproveRequestProvider(): TRegxDataProvider
    {
        return TRegxDataProvider::cross(
            self::requestGroupMembershipProvider(),
            self::approveUserProvider(),
        );
    }

    #[DataProvider("approveUserProvider")]
    public function testApproveNonexistentRequest($approveUserFunc)
    {
        global $USER;
        $this->switchUser("Normal");
        $uid = $USER->uid;
        $this->switchUser("EmptyPIGroupOwner");
        $piGroup = $USER->getPIGroup();
        try {
            $this->expectException(Exception::class); // FIXME more specific exception type
            $approveUserFunc($uid, $piGroup->gid);
        } finally {
            $this->switchUser("Normal");
            ensureUserNotInPIGroup($piGroup);
        }
    }

    #[DataProvider("requestGroupMembershipAndApproveRequestProvider")]
    public function testApproveMember($requestMembershipFunc, $approveRequestFunc)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("EmptyPIGroupOwner");
        $pi_uid = $USER->uid;
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        $this->switchUser("Unqualified");
        try {
            $requestMembershipFunc($USER, $pi_group);
            $this->assertRequestedMembership(true, $gid);

            // $second_request_failed = false;
            // try {
            $requestMembershipFunc($USER, $pi_group);
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertRequestedMembership(true, $gid);

            $this->cancelRequestGroupMembership($gid);
            $this->assertRequestedMembership(false, $gid);

            $requestMembershipFunc($USER, $pi_group);
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $approve_uid = $SSO["user"];
            $this->switchUser("EmptyPIGroupOwner");
            $approveRequestFunc($approve_uid, $gid);
            $this->switchUser("Unqualified");

            $this->assertFalse($pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->memberUIDExists($USER->uid));
            $this->assertTrue($USER->getFlag(UserFlag::QUALIFIED));

            // $third_request_failed = false;
            // try {
            $requestMembershipFunc($USER, $pi_group);
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedMembership(false, $gid);
            $this->assertFalse($pi_group->requestExists($USER));
        } finally {
            $this->switchUser("Unqualified");
            ensureUserNotInPIGroup($pi_group);
            $this->assertGroupMembers($pi_group, [$pi_uid]);
        }
    }
}
