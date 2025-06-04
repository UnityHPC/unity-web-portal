<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;
use UnityWebPortal\lib\UnityGroup;

class NewUserTest extends TestCase
{
    private function assertNumberGroupRequests(int $x)
    {
        global $USER, $SQL;
        $this->assertEquals($x, count($SQL->getRequestsByUser($USER->getUID())));
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
        $SQL->deleteRequestsByUser($USER->getUID());
        $org = $USER->getOrgGroup();
        if ($org->inOrg($USER)) {
            $org->removeUser($USER);
            assert(!$org->inOrg($USER));
        }
        if ($USER->exists()) {
            $USER->getLDAPUser()->delete();
            assert(!$USER->exists());
        }
        $all_users_group = $LDAP->getUserGroup();
        $all_member_uids = $all_users_group->getAttribute("memberuid");
        $new_uids = array_diff($all_member_uids, [$USER->getUID()]);
        if (in_array($USER->getUID(), $all_member_uids)) {
            $all_users_group->setAttribute(
                "memberuid",
                array_diff($all_member_uids, [$USER->getUID()])
            );
            $all_users_group->write();
            assert(!in_array($USER->getUID(), $all_users_group->getAttribute("memberuid")));
        }
    }

    private function ensureOrgGroupDoesNotExist()
    {
        global $USER;
        $org_group = $USER->getOrgGroup();
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
        global $USER;
        if ($USER->getPIGroup()->exists()) {
            $USER->getPIGroup()->getLDAPPIGroup()->delete();
            assert(!$USER->getPIGroup()->exists());
        }
    }

    public function testCreateUserByJoinGoup()
    {
        global $USER, $SQL, $LDAP;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi_group = $USER->getPIGroup();
        switchUser(...getNonExistentUser());
        $this->assertTrue(!$USER->exists());
        $this->assertTrue(!$USER->getOrgGroup()->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertTrue(!$pi_group->userExists($USER));
        $this->assertNumberGroupRequests(0);
        try {
            $this->requestGroupMembership($pi_group->getPIUID());
            $this->assertNumberGroupRequests(1);

            // $second_request_failed = false;
            // try {
                $this->requestGroupMembership($pi_group->getPIUID());
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertNumberGroupRequests(1);

            $this->cancelAllRequests();
            $this->assertNumberGroupRequests(0);

            $this->requestGroupMembership($pi_group->getPIUID());
            $this->assertTrue($pi_group->requestExists($USER));
            $this->assertNumberGroupRequests(1);

            $pi_group->approveUser($USER);
            $this->assertTrue(!$pi_group->requestExists($USER));
            $this->assertNumberGroupRequests(0);
            $this->assertTrue($pi_group->userExists($USER));
            $this->assertTrue($USER->exists());
            $this->assertTrue($USER->getOrgGroup()->exists());

            // $third_request_failed = false;
            // try {
                $this->requestGroupMembership($pi_group->getPIUID());
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertNumberGroupRequests(0);
            $this->assertTrue(!$pi_group->requestExists($USER));
        } finally {
            $this->ensureUserNotInPIGroup($pi_group);
            $this->ensureUserDoesNotExist();
            $this->ensureOrgGroupDoesNotExist();
        }
    }

    public function testCreateUserByCreateGroup()
    {
        global $USER, $SQL, $LDAP;
        switchuser(...getNonExistentUser());
        $pi_group = $USER->getPIGroup();
        $this->assertTrue(!$USER->exists());
        $this->assertTrue(!$pi_group->exists());
        $this->assertTrue(!$USER->getOrgGroup()->exists());
        try {
            $this->requestGroupCreation();
            $this->assertNumberGroupRequests(1);

            // $second_request_failed = false;
            // try {
                $this->requestGroupCreation();
            // } catch(Exception) {
            //     $second_request_failed = true;
            // }
            // $this->assertTrue($second_request_failed);
            $this->assertNumberGroupRequests(1);

            $this->cancelAllRequests();
            $this->assertNumberGroupRequests(0);

            $this->requestGroupCreation();
            $this->assertNumberGroupRequests(1);

            $pi_group->approveGroup();
            $this->assertNumberGroupRequests(0);
            $this->assertTrue($pi_group->exists());
            $this->assertTrue($USER->exists());
            $this->assertTrue($USER->getOrgGroup()->exists());

            // $third_request_failed = false;
            // try {
                $this->requestGroupCreation();
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertNumberGroupRequests(0);
        } finally {
            $this->ensurePIGroupDoesNotExist();
            $this->ensureUserDoesNotExist();
            $this->ensureOrgGroupDoesNotExist();
        }
    }
}
