<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\exceptions\PhpUnitNoDieException;

class NewUserTest extends TestCase
{
    private function assertNumberGroupRequests(int $x)
    {
        global $USER, $SQL;
        $this->assertEquals($x, count($SQL->getRequestsByUser($USER->getUID())));
    }

    private function requestGroupCreation()
    {
        $redirectedOrDied = false;
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["new_user_sel" => "pi", "eula" => "agree", "confirm_pi" => "agree"]
            );
        } catch (\UnityWebPortal\lib\exceptions\PhpUnitNoDieException) {
            $redirectedOrDied = true;
        }
        $this->assertTrue($redirectedOrDied);
    }

    private function requestGroupMembership(string $gid)
    {
        $redirectedOrDied = false;
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["new_user_sel" => "not_pi", "eula" => "agree", "pi" => $gid]
            );
        } catch (\UnityWebPortal\lib\exceptions\PhpUnitNoDieException) {
            $redirectedOrDied = true;
        }
        $this->assertTrue($redirectedOrDied);
    }

    private function cancelAllRequests()
    {
        $redirectedOrDied = false;
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/new_account.php",
                ["cancel" => "true"] // value of cancel is arbitrary
            );
        } catch (\UnityWebPortal\lib\exceptions\PhpUnitNoDieException) {
            $redirectedOrDied = true;
        }
        $this->assertTrue($redirectedOrDied);
    }

    public function testCreateUserByJoinGoup()
    {
        global $USER, $SQL;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi_group = $USER->getPIGroup();
        switchUser(...getNonExistentUser());
        $this->assertTrue(!$USER->exists());
        $this->assertTrue($pi_group->exists());
        $this->assertTrue(!$pi_group->userExists($USER));
        $this->assertNumberGroupRequests(0);
        try {
            $this->requestGroupMembership($pi_group->getPIUID());
            $this->assertNumberGroupRequests(1);

            $second_request_failed = false;
            try {
                $this->requestGroupMembership($pi_group->getPIUID());
            } catch(Exception) {
                $second_request_failed = true;
            }
            $this->assertTrue($second_request_failed);
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

            $third_request_failed = false;
            try {
                $this->requestGroupMembership($pi_group->getPIUID());
            } catch(Exception) {
                $third_request_failed = true;
            }
            $this->assertTrue($third_request_failed);
            $this->assertNumberGroupRequests(0);
            $this->assertTrue(!$pi_group->requestExists($USER));
        } finally {
            $SQL->deleteRequestsByUser($USER->getUID());
            if ($pi_group->userExists($USER)) {
                $pi_group->removeUser($USER);
            }
            if ($USER->exists()) {
                $USER->getLDAPUser->delete();
                assert(!$USER->exists());
            }
        }
    }

    public function testCreateUserByCreateGroup()
    {
        global $USER, $SQL;
        switchuser(...getNonExistentUser());
        $pi_group = $USER->getPIGroup();
        $this->assertTrue(!$USER->exists());
        $this->assertTrue(!$pi_group->exists());
        try {
            $this->requestGroupCreation();
            $this->assertNumberGroupRequests(1);
            $this->assertNumberGroupRequests(0);

            $second_request_failed = false;
            try {
                $this->requestGroupCreation();
            } catch(Exception) {
                $second_request_failed = true;
            }
            $this->assertTrue($second_request_failed);
            $this->assertNumberGroupRequests(1);

            $this->cancelAllRequests();
            $this->assertNumberGroupRequests(0);

            $this->requestGroupCreation();
            $this->assertNumberGroupRequests(1);

            $pi_group->approveGroup();
            $this->assertNumberGroupRequests(0);
            $this->assertTrue($pi_group->exists());
            $this->assertTrue($USER->exists());

            $third_request_failed = false;
            try {
                $this->requestGroupCreation();
            } catch(Exception) {
                $third_request_failed = true;
            }
            $this->assertTrue($third_request_failed);
            $this->assertNumberGroupRequests(0);
        } finally {
            $SQL->deleteRequestsByUser($USER->getUID());
            if ($pi_group->exists()) {
                $pi_group->getLDAPPIGroup()->delete();
                assert(!$pi_group->exists());
            }
            if ($USER->exists()) {
                $USER->getLDAPUser->delete();
                assert(!$USER->exists());
            }
        }
    }
}
