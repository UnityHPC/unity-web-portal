<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\exceptions\NoDieException;
// use PHPUnit\Framework\Attributes\BackupGlobals;
// use PHPUnit\Framework\Attributes\RunTestsInSeparateProcess;

class UnitySiteTest extends TestCase
{
    public static function SSHKeyProvider()
    {
        global $HTTP_HEADER_TEST_INPUTS;
        $validKeys = [
            "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIB+XqO25MUB9x/pS04I3JQ7rMGboWyGXh0GUzkOrTi7a",
            "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIB+XqO25MUB9x/pS04I3JQ7rMGboWyGXh0GUzkOrTi7a foobar",
            "ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBF/dSI9/7YWeyB8wa4rEWRdeb9pQbrGxZwYFV2ulr0agXdbiJIApp0MWDYlIc9XI+4Y+cVAj66PQ2YaRz44BV+o=",
            "ecdsa-sha2-nistp384 AAAAE2VjZHNhLXNoYTItbmlzdHAzODQAAAAIbmlzdHAzODQAAABhBOr8ZnJPs/mP/1c74P8NsiPL2pq/vKo6u0vtkgqgyZjqJJpPS5rP6EFJkT8DI0Fx9/70jvyH8wGK6tx+/gNElMlZ6P2RyHbDvL4Nh2LAEW3BQ2lbULyElP/ZeXIEQzPxng==",
            "ecdsa-sha2-nistp521 AAAAE2VjZHNhLXNoYTItbmlzdHA1MjEAAAAIbmlzdHA1MjEAAACFBAFmNNrz+B6exxuReTXQJzXUzJ4zB5JTuB8Xtcr79P4tk4SlA5a5ufQlsqMdPRhA76KFaLmONGF1e+vwcQWsj/MbRQE0H56tkZRNa+ch5/YI6iKSffkzpRKogl/uTP4rlpRb1vppsURRYxQ2JBzLYolj8VUV+N0sCwM+8maiOGJYuc4dlQ==",
            "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQC6RjRhmaBfCN9l9qsnjplvatK/a7zb2tdbg7kDj8mWXfbC1zkELLLX/L+5hAySbm8QXPgr18CqcyV9LqK+vJ/aPHRNo3E/mp14pxp0nHpPlMzUV8ybl2uk2kBMXWRweOYfAcA5eJToHVAXJEVBvcwDI1WVG9Nfo5w1UhGSqcn4oQ==",
            implode("", [
                "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAIAQDMHfRgu2HjTAODg+1yAXeZ",
                "alNrT3S0sXv7fqC9/uJW86AHU6l384TpSEoqVbl4cke8lev49ljsEg50Zppo",
                "C4fiP6+nAeBy609VWfcHBmbVDeVdLZiAh2XpNW3Fns6ecM24OPr7kdxuhV8p",
                "KTMupXYc/mEUdKTB7DiQcRWcLp8BhX14K3PuFbiprqnacoeiu1In9SLKZd2E",
                "4vg2TrhptdZuuav4WX0r2s2uUwAz+7jpXWYoXUUjfmImEg6h9ETCzKGFwHYA",
                "Tn879WW+28RUOIurfUUU5njnGmtVoWG1s0L7JpoJfu16ePdcPCEpn92coP8D",
                "pFw10iQwh4AsjIdEVYEYfjtxbdk5TqACkfgKo+h9G3nNXO6x8mGjhfNa6CHF",
                "+wVJ4RrJWdhBGfKog+CtD+NHDYXFbyciGT2CtGTFay182DdUg2MoXn4eSmPE",
                "qZJ+kHtJ0mwe0nKtLNwik4c/54X1rZLETauEFMKRE3/JSlAdAMm0jZNW7fad",
                "QOJHB63q/yxyCFLztLrtzhZlBH0DoGuGdHxznbKYDctcQypztP2aG4G+W1go",
                "zwNmwsJRLY3pWos377QRUOq0w3qvcw9BZwigfqixGWHbC2JUyxqoa4opez6z",
                "oDH+tjvJQ0iKv9DQrVOdh8e6NzHMlSnri3r34K5NzDIvV3uROO1yC7pbHogO",
                "S2XmO+nnJYlnINhitXe9+gcfkcEwZs14lxrxcAElJmJOSPE+uui80ZMUHd6C",
                "lemoptFE4cAhdczmkQXURbpzWguHRSWk+5/AXb5r1P7AYpaZSRFfOqy4oB/v",
                "6rKTBjplH7LYi3otykeB1PooHnUfpziOLLFq5ghVdCU8R10yE43drDRnu8dF",
                "pirxBF6AAUzQpUMbYae9BvVTPMWAyM2Wn5P9EUZ+hngvhDlyoZBoNCeqEeWN",
                "6l4KcQVPZwyg1b1PhkAtVzhss3mJQ0Xsqabp2cQvvj+Z/rfQyZKJlAiv2gKd",
                "0W+E5zTd0TqA50JZLKOtPhMsEXUqKop4H6OcJ+SDqqNWzGdnYJHYccQ7y/2I",
                "XrqBlW0gs6BX04Yx+5LusnLBKH8D2MB9kvASPKopzzcF2KFsIw0pLkEc0cVP",
                "oY5gwy05JTuKYoxzIbePgM8KV8rgQ1it442LHEAo5k/6GwVkl/6aQDCwmQV2",
                "YhBxfioyOZZLTCG95ANHaz19H7M+T4BY/d3lUD9FsFcPmY7Ikj6Ma0YMGmvg",
                "ghdIvaXmCxEyIQRi+lpcjPVHV3MzELgNTRDDVkM0TFXlGSBv63XRjos0kbDN",
                "kOo2wLmTModCFuudLDGxOjYriMKdkXmU4Tc7wZSGgngZch49u9b3A6RVwxa2",
                "0LUkuAXOS1EvyBqOcQ1m5RklzwPuK0FD+9qVNHPFSNpRsXbH/mljqlR8MYyd",
                "DGphZW5vPmJ1RGhO8EOkQIk6bZz46Y8U4fVsvSslBX1TWczmAZ6RPA/rFg9R",
                "KAehmze5GJLa0ypVcD86ILJftBd7a+Rzx7G9liLR5HTMv+3k/cbYLiahTQ33",
                "thHK0jiB2DLa0D2tXmQEdEHR1lHlGBwLr5XK+fOFbYKAyhIt63aEL9hmdUBL",
                "DFQfjBLbVEToGonSM54diks9Nesy2wrVYe4bWCmj+TCut0cDXtgQSxiSJhoD",
                "kS2gYIEX6Rrc4ETMhqtfG7LgH2wHeVvJ+wjT/uNQ+8c9Eft4/NZfVpK1vru1",
                "A31ZGqlZKuBxnO4Cd6PiwzBk2YKMul9QpXJxKGH2X0wwqc9wYk4IoydzMaft",
                "nFI+q+ALNpU9BneMJ/FFhbP9NaHVBnQtzs9vNbsBPLEBTyZNfihuJtExRjDc",
                "Tj/tRvrOHsKWC+OdtMJT1MemgHw+/zXG59BwNOfxsStEV79O+F68g5I4Feom",
                "W9fPZYw6d/xLWqPXsxigPPUcH/JXGQ3+p1L+ChOiFvhCTMciJ3+7gc1huLWW",
                "ZqZtOTVxXWKMdia/ox31MqWxWiZcvHOJopum1RmR/OBwtaahSl27LmorLrr2",
                "QPRrtY+OVDBeeNQLNk9/aSzYcMWO66cju2C2Myvadfb6o8bTjw6rUJDSqlG+",
                "pvAhBhQvjxsrcPkT9BspFE+r/SPxWExKWL5djQYRit2druxBtw6Y+ylIg9CZ",
                "JMTf1IBjveBUySF+gOonShB1nLmRZ9zX2hwJSXqYbGQCqzfwRWwPYSnQak0s",
                "kh4J1p7OYFgZCuxkiSaEikpbuHJWAz1cpYsomXQ7l/m+F05JFfAm2LvNjVoe",
                "Ablw+Tj7T/Fx7x63b1CU4Wy3L7Ho25i89fksFhsV/fBk9c3QqSkwpkiDVnvY",
                "eIhM349An75ncfBjToQT5o5Ayn0ritOUHh5NW+SS+955CFFM8ZQhxZaluWKc",
                "rvjwN/UCtBWjGTu7JpwOnHuTTj03ti2wcYsEKNpPB/Nm3kql+UbARmsq24j5",
                "foxjN6gmiKVtuho7SDMsu11UGgidKCiHAM3/o4Im53awfRJiqoouJNTisSuh",
                "xHp7b+4Z14+CUPfQcKkyYCSzrptrJhg2FO5vz1YZOuExIm3deDfcPsK5vg4L",
                "iLam6FJ3qqonXP6krCuH/crJYLTPBJn6eX3noL7TjCqiMWEtLmGj0431Ybcr",
                "gG7Fy2a+VWwcB6w0nzyxbqg16AP+luuqHxfVsvP6Uyde4C7LPeB3r3GhAfuU",
                "Nxnpz/bXGxbJu3+aCnbtaZMzGJ6UFBeJp8MtlmVajDnjx3oEuOGGmobTlaop",
                "HYVsQ3ySfQ==",
            ])
        ];
        $validKeysArgs = array_map(function($x){return [true, $x];}, $validKeys);
        $invalidKeysArgs = array_map(function($x){return [false, $x];}, $HTTP_HEADER_TEST_INPUTS);
        return $validKeysArgs + $invalidKeysArgs;
    }

    #[DataProvider("SSHKeyProvider")]
    public function testTestValidSSHKey(bool $expected, string $key)
    {
        $this->assertEquals($expected, UnitySite::testValidSSHKey($key));
    }

    public function testArrayGetOrBadRequestReturnsValueWhenKeyExists()
    {
        $array = [
            "a" => [
                "b" => [
                    "c" => 123
                ]
            ]
        ];
        $result = UnitySite::arrayGetOrBadRequest($array, "a", "b", "c");
        $this->assertSame(123, $result);
    }

    public function testArrayGetOrBadRequestReturnsArrayWhenTraversingPartially()
    {
        $array = [
            "foo" => [
                "bar" => "baz"
            ]
        ];
        $result = UnitySite::arrayGetOrBadRequest($array, "foo");
        $this->assertSame(["bar" => "baz"], $result);
    }

    public function testArrayGetOrBadRequestThrowsOnMissingKeyFirstLevel()
    {
        $array = ["x" => 1];
        $this->expectException(NoDieException::class);
        $this->expectExceptionMessage('["y"]');
        UnitySite::arrayGetOrBadRequest($array, "y");
    }

    public function testArrayGetOrBadRequestThrowsOnMissingKeyNested()
    {
        $array = ["a" => []];
        $this->expectException(NoDieException::class);
        // Should include both levels
        $this->expectExceptionMessage('["a","b"]');
        UnitySite::arrayGetOrBadRequest($array, "a", "b");
    }

    public function testArrayGetOrBadRequestThrowsWhenValueIsNullButKeyNotSet()
    {
        $array = ["a" => null];
        $this->expectException(NoDieException::class);
        $this->expectExceptionMessage('["a"]');
        UnitySite::arrayGetOrBadRequest($array, "a");
    }

    public function testArrayGetOrBadRequestReturnsValueWhenValueIsFalsyButSet()
    {
        $array = ["a" => 0];
        $result = UnitySite::arrayGetOrBadRequest($array, "a");
        $this->assertSame(0, $result);
    }

    // I suspect that this test could have unexpected interactions with other tests.
    // even with RunTestsInSeparateProcess and BackupGlobalState, http_response_code()
    // still persists to the next test. header("HTTP/1.1 false") puts it back to its
    // initial value, but this is a hack and does not inspire confidence.
    // #[BackupGlobals(true)]
    // #[RunTestsInSeparateProcess]
    // public function testHeaderResponseCode()
    // {
    //     $this->assertEquals(false, http_response_code());
    //     $this->assertArrayNotHasKey("SERVER_PROTOCOL", $_SERVER);
    //     try {
    //         $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
    //         UnitySite::headerResponseCode(400);
    //         $this->assertEquals(400, http_response_code());
    //         UnitySite::headerResponseCode(401);
    //         $this->assertEquals(401, http_response_code());
    //     } finally {
    //         unset($_SERVER["SERVER_PROTOCOL"]);
    //         header("HTTP/1.1 false");
    //     }
    // }
}
