<?php

use UnityWebPortal\lib\exceptions\SSOException;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnitySSO;

class UnitySSOTest extends UnityWebPortalTestCase
{
    public function testMultipleAttributeValues()
    {
        $PREVIOUS_SERVER = $_SERVER;
        try {
            $_SERVER = array_merge($_SERVER, [
                "REMOTE_USER" => "user2003@org1.test",
                "givenName" => "foo;foo",
                "sn" => "bar;bar",
                "mail" => "user2003@org1.test;user2003@org1.test",
            ]);
            $sso = UnitySSO::getSSO();
            $this->assertEquals("foo", $sso["firstname"]);
            $this->assertEquals("bar", $sso["lastname"]);
            $this->assertEquals("user2003@org1.test", $sso["mail"]);
        } finally {
            $_SERVER = $PREVIOUS_SERVER;
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
            $_SERVER = $PREVIOUS_SERVER;
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
            $_SERVER = $PREVIOUS_SERVER;
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
            $_SERVER = $PREVIOUS_SERVER;
        }
        $this->assertEquals("foobar@baz", $sso["mail"]);
    }

    public static function validEppnProvider()
    {
        return [["foo@bar.edu", "foo_bar_edu", "bar_edu"]];
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
            $_SERVER = $PREVIOUS_SERVER;
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
            $_SERVER = $PREVIOUS_SERVER;
        }
    }

    public static function invalidEppnProvider()
    {
        return [
            ["foo"], // missing @
            ["foo@bar@baz"], // too many @
            [""],
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
            $_SERVER = $PREVIOUS_SERVER;
        }
    }
}
