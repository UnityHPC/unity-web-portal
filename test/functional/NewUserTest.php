<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityOrg;
use UnityWebPortal\lib\UnitySQL;

class NewUserTest extends TestCase
{
    public static function provider() {
        return [
            getNonExistentUserAndExpectedUIDGIDNoCustomMapping(),
            getNonExistentUserAndExpectedUIDGIDWithCustomMapping(),
        ];
    }

    private function assertRequestedPIGroup(bool $expected)
    {
        global $USER, $SQL;
        $this->assertEquals(
            $expected,
            $SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI)
        );
    }

    private function assertRequestedMembership(bool $expected, string $gid)
    {
        global $USER, $SQL;
        $this->assertEquals(
            $expected,
            $SQL->requestExists($USER->uid, $gid)
        );
    }

    private function requestGroupCreation()
    {
        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["new_user_sel" => "pi", "eula" => "agree", "confirm_pi" => "agree"]
        );
    }

    private function requestGroupMembership(string $gid)
    {
        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["new_user_sel" => "not_pi", "eula" => "agree", "pi" => $gid]
        );
    }

    private function cancelAllRequests()
    {
        http_post(
            __DIR__ . "/../../webroot/panel/new_account.php",
            ["cancel" => "true"] // value of cancel is arbitrary
        );
    }

    private function approveUserByAdmin($gid, $uid)
    {
        http_post(
            __DIR__ . "/../../webroot/admin/pi-mgmt.php",
            [
                "form_type" => "reqChild",
                "action" => "Approve",
                "pi" => $gid,
                "uid" => $uid,
            ]
        );
    }

    private function approveUserByPI($uid)
    {
        http_post(
            __DIR__ . "/../../webroot/panel/pi.php",
            [
                "form_type" => "userReq",
                "action" => "Approve",
                "uid" => $uid,
            ]
        );
    }

    private function approveGroup($uid)
    {
        http_post(
            __DIR__ . "/../../webroot/admin/pi-mgmt.php",
            [
                "form_type" => "req",
                "action" => "Approve",
                "uid" => $uid,
            ]
        );
    }

    // delete requests made by that user
    // delete user entry
    // delete user group entry
    // remove user from org group
    // remove user from "all users" group
    // does not remove user from PI groups
    private function ensureUserDoesNotExist()
    {
        global $USER, $SQL, $LDAP, $REDIS;
        $SQL->deleteRequestsByUser($USER->uid);
        if ($USER->exists()) {
            $org = $USER->getOrgGroup();
            if ($org->exists() and $org->inOrg($USER)) {
                $org->removeUser($USER);
                assert(!$org->inOrg($USER));
            }
            $LDAP->getUserEntry($USER->uid)->delete();
            assert(!$USER->exists());
        }
        if ($USER->getGroupEntry()->exists()) {
            $USER->getGroupEntry()->delete();
            assert(!$USER->getGroupEntry()->exists());
        }
        $all_users_group = $LDAP->getUserGroup();
        $all_member_uids = $all_users_group->getAttribute("memberuid");
        if (in_array($USER->uid, $all_member_uids)) {
            $all_users_group->setAttribute(
                "memberuid",
                // array_diff will break the contiguity of the array indexes
                // ldap_mod_replace requires contiguity, array_values restores contiguity
                array_values(array_diff($all_member_uids, [$USER->uid]))
            );
            $all_users_group->write();
            assert(!in_array($USER->uid, $all_users_group->getAttribute("memberuid")));
        }
        $REDIS->removeCacheArray("sorted_users", "", $USER->uid);
    }

    private function ensureOrgGroupDoesNotExist()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        $org_group = $LDAP->getOrgGroupEntry($SSO["org"]);
        if ($org_group->exists()) {
            $org_group->delete();
            assert(!$org_group->exists());
        }
        $REDIS->removeCacheArray("sorted_orgs", "", $SSO["org"]);
    }

    private function ensureUserNotInPIGroup(UnityGroup $pi_group)
    {
        global $USER, $REDIS;
        if ($pi_group->userExists($USER)) {
            $pi_group->removeUser($USER);
            assert(!$pi_group->userExists($USER));
        }
        $REDIS->removeCacheArray($pi_group->gid, "members", $USER->uid);
    }

    private function ensurePIGroupDoesNotExist()
    {
        global $USER, $LDAP, $REDIS;
        $gid = $USER->getPIGroup()->gid;
        if ($USER->getPIGroup()->exists()) {
            $LDAP->getPIGroupEntry($gid)->delete();
            assert(!$USER->getPIGroup()->exists());
        }
        $REDIS->removeCacheArray("sorted_groups", "", $gid);
    }

    #[DataProvider("provider")]
    public function testCreateUserByJoinGoupByPI($user_to_create_args, $expected_uid_gid)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        $pi_user_args = getUserIsPIHasNoMembersNoMemberRequests();
        switchUser(...$pi_user_args);
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        switchUser(...$user_to_create_args);
        $this->assertTrue(!$USER->exists());
        $newOrg = new UnityOrg($SSO["org"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        $this->assertTrue(!$newOrg->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertTrue(!$pi_group->userExists($USER));
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

            $this->cancelAllRequests();
            $this->assertRequestedMembership(false, $gid);

            $this->requestGroupMembership($pi_group->gid);
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $REDIS->flushAll(); // regression test: flush used to break requests

            $approve_uid = $SSO["user"];
            switchUser(...$pi_user_args);
            $this->approveUserByPI($approve_uid);
            switchUser(...$user_to_create_args);

            $this->assertTrue(!$pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->userExists($USER));
            $this->assertTrue($USER->exists());
            $this->assertTrue($newOrg->exists());

            $user_entry = $LDAP->getUserEntry($approve_uid);
            $user_group_entry = $LDAP->getGroupEntry($approve_uid);
            $this->assertEquals($expected_uid_gid, $user_entry->getAttribute("uidnumber")[0]);
            $this->assertEquals($expected_uid_gid, $user_group_entry->getAttribute("gidnumber")[0]);

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
            switchUser(...$user_to_create_args);
            $this->ensureOrgGroupDoesNotExist();
            $this->ensureUserNotInPIGroup($pi_group);
            $this->ensureUserDoesNotExist();
        }
    }

    public function testCreateMultipleUsersByJoinGoupByPI()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        $pi_user_args = getUserIsPIHasNoMembersNoMemberRequests();
        switchUser(...$pi_user_args);
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        $this->assertTrue($pi_group->exists());
        $users_to_create_args = getNonexistentUsersWithExistentOrg();
        try {
            foreach ($users_to_create_args as $user_to_create_args) {
                switchUser(...$user_to_create_args);
                $this->assertTrue(!$USER->exists());
                $this->assertTrue(!$pi_group->userExists($USER));
                $this->assertRequestedMembership(false, $gid);
                $this->requestGroupMembership($pi_group->gid);
                $this->assertRequestedMembership(true, $gid);
                $approve_uid = $USER->uid;
                switchUser(...$pi_user_args);
                // $this->assertTrue(!$pi_group->userExists($USER));
                $this->approveUserByPI($approve_uid);
                switchUser(...$user_to_create_args);
                $this->assertTrue(!$pi_group->requestExists($USER));
                $this->assertRequestedMembership(false, $gid);
                $this->assertTrue($pi_group->userExists($USER));
                $this->assertTrue($USER->exists());
            }
        } finally {
            foreach ($users_to_create_args as $user_to_create_args) {
                switchUser(...$user_to_create_args);
                $this->ensureUserNotInPIGroup($pi_group);
                $this->ensureUserDoesNotExist();
            }
        }
    }

    #[DataProvider("provider")]
    public function testCreateUserByJoinGoupByAdmin($user_to_create_args, $expected_uid_gid)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->gid;
        switchUser(...$user_to_create_args);
        $this->assertTrue(!$USER->exists());
        $newOrg = new UnityOrg($SSO["org"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        $this->assertTrue(!$newOrg->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertTrue(!$pi_group->userExists($USER));
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

            $this->cancelAllRequests();
            $this->assertRequestedMembership(false, $gid);

            $this->requestGroupMembership($pi_group->gid);
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $REDIS->flushAll(); // regression test: flush used to break requests

            $approve_uid = $SSO["user"];
            switchUser(...getAdminUser());
            $this->approveUserByAdmin($gid, $approve_uid);
            switchUser(...$user_to_create_args);

            $this->assertTrue(!$pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->userExists($USER));
            $this->assertTrue($USER->exists());
            $this->assertTrue($newOrg->exists());

            $user_entry = $LDAP->getUserEntry($approve_uid);
            $user_group_entry = $LDAP->getGroupEntry($approve_uid);
            $this->assertEquals($expected_uid_gid, $user_entry->getAttribute("uidnumber")[0]);
            $this->assertEquals($expected_uid_gid, $user_group_entry->getAttribute("gidnumber")[0]);

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
            switchUser(...$user_to_create_args);
            $this->ensureOrgGroupDoesNotExist();
            $this->ensureUserNotInPIGroup($pi_group);
            $this->ensureUserDoesNotExist();
        }
    }

    #[DataProvider("provider")]
    public function testCreateUserByCreateGroup($user_to_create_args, $expected_uid_gid)
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchuser(...$user_to_create_args);
        $pi_group = $USER->getPIGroup();
        $this->assertTrue(!$USER->exists());
        $this->assertTrue(!$pi_group->exists());
        $newOrg = new UnityOrg($SSO["org"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        $this->assertTrue(!$newOrg->exists());
        try {
            $this->requestGroupCreation();
            $this->assertRequestedPIGroup(true);

            // $second_request_failed = false;
            // try {
            $this->requestGroupCreation();
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertRequestedPIGroup(true);

            $this->cancelAllRequests();
            $this->assertRequestedPIGroup(false);

            $this->requestGroupCreation();
            $this->assertRequestedPIGroup(true);

            $REDIS->flushAll(); // regression test: flush used to break requests

            $approve_uid = $SSO["user"];
            switchUser(...getAdminUser());
            $this->approveGroup($approve_uid);
            switchUser(...$user_to_create_args);

            $this->assertRequestedPIGroup(false);
            $this->assertTrue($pi_group->exists());
            $this->assertTrue($USER->exists());
            $this->assertTrue($newOrg->exists());

            $user_entry = $LDAP->getUserEntry($approve_uid);
            $user_group_entry = $LDAP->getGroupEntry($approve_uid);
            $this->assertEquals($expected_uid_gid, $user_entry->getAttribute("uidnumber")[0]);
            $this->assertEquals($expected_uid_gid, $user_group_entry->getAttribute("gidnumber")[0]);

            // $third_request_failed = false;
            // try {
            $this->requestGroupCreation();
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedPIGroup(false);
        } finally {
            switchUser(...$user_to_create_args);
            $this->ensureOrgGroupDoesNotExist();
            $this->ensurePIGroupDoesNotExist();
            $this->ensureUserDoesNotExist();
        }
    }
}
