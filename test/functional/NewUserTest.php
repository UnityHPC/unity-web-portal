<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnityOrg;
use UnityWebPortal\lib\UnitySQL;

class NewUserTest extends TestCase
{
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

    // delete requests made by that user
    // delete user entry
    // remove user from org group
    // remove user from "all users" group
    // does not remove user from PI groups
    private function ensureUserDoesNotExist()
    {
        global $USER, $SQL, $LDAP;
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
        $all_users_group = $LDAP->getUserGroup();
        $all_member_uids = $all_users_group->getAttribute("memberuid");
        $new_uids = array_diff($all_member_uids, [$USER->uid]);
        if (in_array($USER->uid, $all_member_uids)) {
            $all_users_group->setAttribute(
                "memberuid",
                array_diff($all_member_uids, [$USER->uid])
            );
            $all_users_group->write();
            assert(!in_array($USER->uid, $all_users_group->getAttribute("memberuid")));
        }
    }

    private function ensureOrgGroupDoesNotExist()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        $org_group = new UnityOrg($SSO["org"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        if ($org_group->exists()) {
            $org_group->getLDAPOrgGroup()->delete();
            assert(!$org_group->exists());
        }
    }

    private function ensureUserNotInPIGroup(UnityGroup $pi_group)
    {
        global $USER;
        if ($pi_group->userExists($USER)) {
            $pi_group->removeUser($USER);
            assert(!$pi_group->userExists($USER));
        }
    }

    private function ensurePIGroupDoesNotExist()
    {
        global $USER, $LDAP;
        if ($USER->getPIGroup()->exists()) {
            $LDAP->getPIGroupEntry($USER->getPIGroup()->getPIUID())->delete();
            assert(!$USER->getPIGroup()->exists());
        }
    }

    public function testCreateUserByJoinGoup()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi_group = $USER->getPIGroup();
        $gid = $pi_group->getPIUID();
        switchUser(...getNonExistentUser());
        $this->assertTrue(!$USER->exists());
        $newOrg = new UnityOrg($SSO["org"], $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        $this->assertTrue(!$newOrg->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertTrue(!$pi_group->userExists($USER));
        $this->assertRequestedMembership(false, $gid);
        try {
            $this->requestGroupMembership($pi_group->getPIUID());
            $this->assertRequestedMembership(true, $gid);

            // $second_request_failed = false;
            // try {
                $this->requestGroupMembership($pi_group->getPIUID());
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertRequestedMembership(true, $gid);

            $this->cancelAllRequests();
            $this->assertRequestedMembership(false, $gid);

            $this->requestGroupMembership($pi_group->getPIUID());
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertRequestedMembership(true, $gid);

            $REDIS->flushAll(); // regression test: flush used to break requests

            $pi_group->approveUser($USER);
            $this->assertTrue(!$pi_group->requestExists($USER));
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue($pi_group->userExists($USER));
            $this->assertTrue($USER->exists());
            $this->assertTrue($newOrg->exists());

            // $third_request_failed = false;
            // try {
                $this->requestGroupMembership($pi_group->getPIUID());
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedMembership(false, $gid);
            $this->assertTrue(!$pi_group->requestExists($USER));
        } finally {
            $this->ensureOrgGroupDoesNotExist();
            $this->ensureUserNotInPIGroup($pi_group);
            $this->ensureUserDoesNotExist();
        }
    }

    public function testCreateUserByCreateGroup()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchuser(...getNonExistentUser());
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

            $pi_group->approveGroup();
            $this->assertRequestedPIGroup(false);
            $this->assertTrue($pi_group->exists());
            $this->assertTrue($USER->exists());
            $this->assertTrue($newOrg->exists());

            // $third_request_failed = false;
            // try {
                $this->requestGroupCreation();
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedPIGroup(false);
        } finally {
            $this->ensureOrgGroupDoesNotExist();
            $this->ensurePIGroupDoesNotExist();
            $this->ensureUserDoesNotExist();
        }
    }
}
