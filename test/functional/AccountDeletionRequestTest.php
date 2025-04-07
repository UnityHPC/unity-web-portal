<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;

class AccountDeletionRequestTest extends TestCase {
    private static $initial_has_requested_deletion;

    public static function setUpBeforeClass(): void{
        global $USER;
        self::$initial_has_requested_deletion = $USER->hasRequestedAccountDeletion();
    }

    protected function tearDown(): void {
        global $USER, $SQL;
        if (!self::$initial_has_requested_deletion && $USER->hasRequestedAccountDeletion()) {
            $SQL->removeAccountDeletionRequest($USER->getUID());
        }
        if (self::$initial_has_requested_deletion && !$USER->hasRequestedAccountDeletion()) {
            $USER->requestAccountDeletion();
        }
    }

    private function request_account_deletion(){
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "account_deletion_request"]
        );
    }

    private function test_request_account_deletion(){
        global $USER, $SQL;
        if (self::$initial_has_requested_deletion) {
            $this->expectException(BadRequestException::class);
        }
        $this->request_account_deletion();
        $this->assertTrue($SQL->accDeletionRequestExists($USER->getUID()));
    }

    public function test_request_account_deletion_once(){
        $this->test_request_account_deletion();
    }

    public function test_request_become_pi_twice(){
        $this->test_request_account_deletion();
        $this->test_request_account_deletion();
    }
}
