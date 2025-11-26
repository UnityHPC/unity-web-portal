<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\UnityOrg;
use UnityWebPortal\lib\UnitySQL;

class PIBecomeApproveTest extends TestCase
{
    private function assertRequestedPIGroup(bool $expected)
    {
        global $USER, $SQL;
        $this->assertEquals(
            $expected,
            $SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI),
        );
    }

    private function requestGroupCreation()
    {
        http_post(__DIR__ . "/../../webroot/panel/account.php", [
            "form_type" => "pi_request",
            "tos" => "agree",
            "account_policy" => "agree",
        ]);
    }

    private function cancelRequestGroupCreation()
    {
        http_post(__DIR__ . "/../../webroot/panel/account.php", [
            "form_type" => "cancel_pi_request",
        ]);
    }

    private function approveGroup($uid)
    {
        http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
            "form_type" => "req",
            "action" => "Approve",
            "uid" => $uid,
        ]);
    }

    public function testApprovePI()
    {
        global $USER, $SSO, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $user_to_qualify_args = getUnqualifiedUser();
        switchuser(...$user_to_qualify_args);
        $pi_group = $USER->getPIGroup();
        $this->assertTrue($USER->exists());
        $this->assertTrue(!$pi_group->exists());
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

            $this->cancelRequestGroupCreation();
            $this->assertRequestedPIGroup(false);

            $this->requestGroupCreation();
            $this->assertRequestedPIGroup(true);

            $approve_uid = $SSO["user"];
            switchUser(...getAdminUser());
            $this->approveGroup($approve_uid);
            switchUser(...$user_to_qualify_args);

            $this->assertRequestedPIGroup(false);
            $this->assertTrue($pi_group->exists());
            $this->assertTrue($USER->isQualified());

            // $third_request_failed = false;
            // try {
            $this->requestGroupCreation();
            // } catch(Exception) {
            //     $third_request_failed = true;
            // }
            // $this->assertTrue($third_request_failed);
            $this->assertRequestedPIGroup(false);
        } finally {
            switchUser(...$user_to_qualify_args);
            ensurePIGroupDoesNotExist();
        }
    }
}
