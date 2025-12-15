<?php

use UnityWebPortal\lib\UnitySQL;
use UnityWebPortal\lib\UnityGroup;

class PIMemberApproveTest extends UnityWebPortalTestCase
{
    private function requestGroupMembership(string $gid_or_mail)
    {
        http_post(__DIR__ . "/../../webroot/panel/groups.php", [
            "form_type" => "addPIform",
            "tos" => "agree",
            "pi" => $gid_or_mail,
        ]);
    }

    private function cancelRequestGroupMembership($gid)
    {
        http_post(__DIR__ . "/../../webroot/panel/groups.php", [
            "form_type" => "cancelPIForm",
            "pi" => $gid,
        ]);
    }

    private function approveUserByAdmin($gid, $uid)
    {
        http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
            "form_type" => "reqChild",
            "action" => "Approve",
            "pi" => $gid,
            "uid" => $uid,
        ]);
    }

    private function approveUserByPI($uid)
    {
        http_post(__DIR__ . "/../../webroot/panel/pi.php", [
            "form_type" => "userReq",
            "action" => "Approve",
            "uid" => $uid,
        ]);
    }

    public function testApproveNonexistentRequest()
    {
        global $USER;
        $user_args = getNormalUser2();
        switchUser(...$user_args);
        $user = $USER;
        $uid = $USER->uid;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $piUID = $USER->uid;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $this->assertGroupMembers($piGroup, [$piUID]);
        $this->assertEmpty($piGroup->getRequests());
        $this->assertFalse($piGroup->mermberUIDExists($user->uid));
        $this->assertEmpty($piGroup->getRequests());
        try {
            $this->expectException(Exception::class); // FIXME more specific exception type
            $this->approveUserByPI($uid);
        } finally {
            switchUser(...$user_args);
            ensureUserNotInPIGroup($piGroup);
        }
    }
    public function testApproveMemberByPI()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $user_to_approve_args = getUnqualifiedUser();
        $pi_user_args = getUserIsPIHasNoMembersNoMemberRequests();
        switchUser(...$pi_user_args);
        $pi_uid = $USER->uid;
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        switchUser(...$user_to_approve_args);
        $this->assertTrue($USER->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertGroupMembers($pi_group, [$pi_uid]);
        $this->assertTrue(!$pi_group->mermberUIDExists($USER->uid));
        $this->assertRequestedMembership(false, $gid);
        try {
            $this->requestGroupMembership($pi_group->gid);
            $this->assertRequestedMembership(true, $gid);

            // $second_request_failed = false;
            // try {
            $this->requestGroupMembership($pi_group->gid);
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertRequestedMembership(true, $gid);

            $this->cancelRequestGroupMembership($gid);
            $this->assertRequestedMembership(false, $gid);

            $this->requestGroupMembership($pi_group->gid);
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $approve_uid = $SSO["user"];
            switchUser(...$pi_user_args);
            $this->approveUserByPI($approve_uid);
            switchUser(...$user_to_approve_args);

            $this->assertTrue(!$pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->mermberUIDExists($USER->uid));
            $this->assertTrue($USER->isQualified());

            // $third_request_failed = false;
            // try {
            $this->requestGroupMembership($pi_group->gid);
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue(!$pi_group->requestExists($USER));
        } finally {
            switchUser(...$user_to_approve_args);
            ensureUserNotInPIGroup($pi_group);
            $this->assertGroupMembers($pi_group, [$pi_uid]);
        }
    }

    public function testApproveMemberByAdmin()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $user_to_approve_args = getUnqualifiedUser();
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi_group = $USER->getPIGroup();
        $pi_uid = $USER->uid;
        $gid = $pi_group->gid;
        switchUser(...$user_to_approve_args);
        $this->assertTrue($USER->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertGroupMembers($pi_group, [$pi_uid]);
        $this->assertTrue(!$pi_group->mermberUIDExists($USER->uid));
        $this->assertRequestedMembership(false, $gid);
        try {
            $this->requestGroupMembership($pi_group->gid);
            $this->assertRequestedMembership(true, $gid);

            // $second_request_failed = false;
            // try {
            $this->requestGroupMembership($pi_group->gid);
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertRequestedMembership(true, $gid);

            $this->cancelRequestGroupMembership($gid);
            $this->assertRequestedMembership(false, $gid);

            $this->requestGroupMembership($pi_group->getOwner()->getMail());
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $approve_uid = $SSO["user"];
            switchUser(...getAdminUser());
            $this->approveUserByAdmin($gid, $approve_uid);
            switchUser(...$user_to_approve_args);

            $this->assertTrue(!$pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->mermberUIDExists($USER->uid));
            $this->assertTrue($USER->isQualified());

            // $third_request_failed = false;
            // try {
            $this->requestGroupMembership($pi_group->gid);
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue(!$pi_group->requestExists($USER));
        } finally {
            switchUser(...$user_to_approve_args);
            ensureUserNotInPIGroup($pi_group);
            $this->assertGroupMembers($pi_group, [$pi_uid]);
        }
    }
}
