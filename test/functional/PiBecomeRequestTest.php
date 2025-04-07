<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;

class PiBecomeRequestTest extends TestCase {
    private static $initial_has_request;

    public static function setUpBeforeClass(): void{
        global $USER, $SQL;
        self::$initial_has_request = $SQL->requestExists($USER->getUID());
    }

    protected function tearDown(): void {
        global $USER, $SQL;
        if (!self::$initial_has_request && $SQL->requestExists($USER->getUID())) {
            $SQL->removeRequest($USER->getUID());
        }
        if (self::$initial_has_request && !$SQL->requestExists($USER->getUID())) {
            $SQL>addRequest($USER->getUID());
        }
    }

    private function request_become_pi(){
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "pi_request"]
        );
    }

    private function test_request_become_pi(){
        global $USER, $SQL;
        if ($USER->isPI()) {
            $this->expectException(BadRequestException::class);
        }
        if ($SQL->requestExists($USER->getUID())){
            $this->expectException(BadRequestException::class);
        }
        if ($SQL->accDeletionRequestExists($USER->getUID())){
            $this->expectException(BadRequestException::class);
        }
        $this->request_become_pi();
        $exists = $SQL->requestExists($USER->getUID());
        $this->assertTrue($exists);
    }

    public function test_request_become_pi_once(){
        $this->test_request_become_pi();
    }

    public function test_request_become_pi_twice(){
        $this->test_request_become_pi();
        $this->test_request_become_pi();
    }
}
