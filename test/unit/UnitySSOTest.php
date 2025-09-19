<?php

namespace UnityWebPortal\lib;

use UnityWebPortal\lib\exceptions\SSOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnitySSOTest extends TestCase
{
    public function testMultipleAttributeValues()
    {
        $PREVIOUS_SERVER = $_SERVER;
        $two_vars = getMultipleValueAttributesAndExpectedSSO();
        $attributes = $two_vars[0];
        $expectedSSO = $two_vars[1];
        try {
            $_SERVER = array_merge($_SERVER, $attributes);
            $sso = UnitySSO::getSSO();
            $this->assertEquals($expectedSSO["firstname"], $sso["firstname"]);
            $this->assertEquals($expectedSSO["lastname"], $sso["lastname"]);
            $this->assertEquals($expectedSSO["mail"], $sso["mail"]);
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
    }

    public function testUnsetAttribute()
    {
        $PREVIOUS_SERVER = $_SERVER;
        $this->expectException(SSOException::class);
        try {
            unset($_SERVER["REMOTE_USER"]);
            $sso = UnitySSO::getSSO();
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
    }

    public function testEmptyAttribute()
    {
        $PREVIOUS_SERVER = $_SERVER;
        $this->expectException(SSOException::class);
        try {
            $_SERVER["REMOTE_USER"] = "";
            $sso = UnitySSO::getSSO();
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
    }

    public function testFallbackAttribute()
    {
        $PREVIOUS_SERVER = $_SERVER;
        try {
            $_SERVER["REMOTE_USER"] = "foobar@baz";
            $_SERVER["givenName"] = "bar";
            $_SERVER["sn"] = "bar";
            $_SERVER["eppn"] = "foobar@baz";
            unset($_SERVER["mail"]);
            $sso = UnitySSO::getSSO();
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
        $this->assertEquals("foobar@baz", $sso["mail"]);
    }

    public static function validEppnProvider() {
        return [
            ["foo@bar.edu", "foo_bar_edu", "bar_edu"],
        ];
    }

    #[DataProvider("validEppnProvider")]
    public function testEppnToUid(string $eppn, string $expectedUID, string $_)
    {
        $PREVIOUS_SERVER = $_SERVER;
        try {
            $_SERVER["REMOTE_USER"] = $eppn;
            $_SERVER["givenName"] = "foo";
            $_SERVER["sn"] = "foo";
            $_SERVER["mail"] = "foo";
            $sso = UnitySSO::getSSO();
            $this->assertEquals($expectedUID, $sso["user"]);
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
    }

    #[DataProvider("validEppnProvider")]
    public function testEppnToOrg(string $eppn, string $_, string $expectedOrg)
    {
        $PREVIOUS_SERVER = $_SERVER;
        try {
            $_SERVER["REMOTE_USER"] = $eppn;
            $_SERVER["givenName"] = "foo";
            $_SERVER["sn"] = "foo";
            $_SERVER["mail"] = "foo";
            $sso = UnitySSO::getSSO();
            $this->assertEquals($expectedOrg, $sso["org"]);
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
    }

    public static function invalidEppnProvider() {
        return [
            ["foo"], // missing @
            ["foo@bar@baz"], // too many @
            [""]
        ];
    }

    #[DataProvider("invalidEppnProvider")]
    public function testInvalidEPPN(string $eppn)
    {
        $this->expectException(SSOException::class);
        $PREVIOUS_SERVER = $_SERVER;
        try {
            $_SERVER["REMOTE_USER"] = $eppn;
            $_SERVER["givenName"] = "foo";
            $_SERVER["sn"] = "foo";
            $_SERVER["mail"] = "foo";
            $sso = UnitySSO::getSSO();
        } finally {
            $_PREVIOUS_SERVER = $_SERVER;
        }
    }
}
