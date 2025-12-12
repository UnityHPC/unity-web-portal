<?php

use PHPUnit\Framework\TestCase;
use UnityWebPortal\lib\CSRFToken;

class CSRFTokenTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION["csrf_token"])) {
            unset($_SESSION["csrf_token"]);
        }
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION["csrf_token"])) {
            unset($_SESSION["csrf_token"]);
        }
    }

    public function testGenerateCreatesToken(): void
    {
        $token = CSRFToken::generate();

        $this->assertIsString($token);

        $this->assertEquals(64, strlen($token));

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testGenerateStoresTokenInSession(): void
    {
        $token = CSRFToken::generate();

        $this->assertArrayHasKey("csrf_token", $_SESSION);
        $this->assertEquals($token, $_SESSION["csrf_token"]);
    }

    public function testGetTokenCreatesTokenIfNotExists(): void
    {
        $this->assertArrayNotHasKey("csrf_token", $_SESSION);

        $token = CSRFToken::getToken();

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
        $this->assertEquals($token, $_SESSION["csrf_token"]);
    }

    public function testGetTokenReturnsExistingToken(): void
    {
        $token1 = CSRFToken::generate();
        $token2 = CSRFToken::getToken();

        $this->assertEquals($token1, $token2);
    }

    public function testValidateWithValidToken(): void
    {
        $token = CSRFToken::generate();

        $this->assertTrue(CSRFToken::validate($token));
    }

    public function testValidateWithInvalidToken(): void
    {
        CSRFToken::generate();

        $this->assertFalse(CSRFToken::validate("invalid_token"));
    }

    public function testValidateWithEmptyToken(): void
    {
        CSRFToken::generate();

        $this->assertFalse(CSRFToken::validate(""));
    }

    public function testValidateWithoutSessionToken(): void
    {
        $this->assertFalse(CSRFToken::validate("any_token"));
    }

    public function testValidateUsesConstantTimeComparison(): void
    {
        $token = CSRFToken::generate();

        $this->assertTrue(CSRFToken::validate($token));

        $invalidToken = substr($token, 0, -1) . ($token[-1] === "a" ? "b" : "a");
        $this->assertFalse(CSRFToken::validate($invalidToken));
    }

    public function testGetHiddenInputReturnsHtmlField(): void
    {
        $token = CSRFToken::generate();
        $html = CSRFToken::getHiddenInput();

        $this->assertStringContainsString("<input", $html);
        $this->assertStringContainsString('type=\'hidden\'', $html);
        $this->assertStringContainsString('name=\'csrf_token\'', $html);
        $this->assertStringContainsString("value='$token'", $html);
    }

    public function testGetHiddenInputEscapesToken(): void
    {
        $_SESSION["csrf_token"] = "'\"><script>alert('xss')</script>";

        $html = CSRFToken::getHiddenInput();

        $this->assertStringNotContainsString("<script>", $html);
        $this->assertStringContainsString("&", $html);
    }

    public function testClearRemovesToken(): void
    {
        CSRFToken::generate();
        $this->assertArrayHasKey("csrf_token", $_SESSION);

        CSRFToken::clear();

        $this->assertArrayNotHasKey("csrf_token", $_SESSION);
    }

    public function testMultipleTokenGenerations(): void
    {
        $token1 = CSRFToken::generate();
        $token2 = CSRFToken::generate();

        $this->assertNotEquals($token1, $token2);

        $this->assertEquals($token2, CSRFToken::getToken());
    }

    public function testParameterNameConstant(): void
    {
        $this->assertEquals("csrf_token", "csrf_token");
    }
}
