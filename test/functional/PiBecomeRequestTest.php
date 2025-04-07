<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;

class PiBecomeRequestTest extends TestCase {
    private function request_become_pi(){
        post(
            "../../webroot/panel/account.php",
            ["form_type" => "pi_request"]
        );
    }

    private function test_request_become_pi(){
        global $USER, $SQL;
        error_log("request exists? " . (string)$SQL->requestExists($USER->getUID()) . "\n");
        if ($USER->isPI()) {
            $this->expectException(BadRequestException::class);
        }
        if ($SQL->requestExists($USER->getUID())){
            $this->expectException(BadRequestException::class);
        }
        $this->request_become_pi();
        $this->assertTrue($SQL->requestExists($USER->getUID()));
        error_log("request exists? " . (string)$SQL->requestExists($USER->getUID()) . "\n");
    }

    protected function setUp(): void {
        ob_start();
    }

    protected function tearDown(): void {
        global $USER, $SQL;
        if ($SQL->requestExists($USER->getUID())){
            $SQL->removeRequest($USER->getUID());
        }
        ob_get_clean();
    }

    public function test_request_become_pi_once(){
        $this->test_request_become_pi();
    }

    public function test_request_become_pi_twice(){
        $this->test_request_become_pi();
        $this->test_request_become_pi();
    }
}
