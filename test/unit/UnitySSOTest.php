<?php

namespace UnityWebPortal\lib;

use UnityWebPortal\lib\exceptions\SSOException;
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
}
