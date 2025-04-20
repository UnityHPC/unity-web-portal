<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UnitySiteTest extends TestCase
{
    public static function SSHKeyProvider()
    {
        return [
            [false, ""],
            [false, "foobar"],
            [false, "1"],
            [false, '{"key": "value"}'],
            [true, "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIB+XqO25MUB9x/pS04I3JQ7rMGboWyGXh0GUzkOrTi7a"],
            [false, "    ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIB+XqO25MUB9x/pS04I3JQ7rMGboWyGXh0GUzkOrTi7a    "],
            //phpcs:disable
            [true, "ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBJNqo8NKTfXgCsaE3ly0tDCfwFuFgJiftup0bIZnRi5bP5QgDN5BFeJfEUPSY/s/GL2hUAjkz3ytGqvadt84W7w="],
            [true, "ssh-rsa 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"],
            //phpcs:enable
        ];
    }

    #[DataProvider("SSHKeyProvider")]
    public function testTestValidSSHKey(bool $expected, string $key)
    {
        $SITE = new UnitySite();
        $this->assertEquals($expected, $SITE->testValidSSHKey($key));
    }

    public static function providerTestGetGithubKeys()
    {
        return [
            # empty
            ["", []],
            # nonexistent user
            ["asdfkljhasdflkjashdflkjashdflkasjd", []],
            # user with no keys
            ["sheldor1510", []],
            # user with 1 key
            //phpcs:disable
            ["simonLeary42", ["ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDGRl6JWPj+Gq2Lz9GjYdUl4/unLoFOyfgeiII1CxutpabPByRJbeuonR0zTpn51tZeYuAUOJBOeKt+Lj4i4UDGl6igpdXXSwkBXl7jxfRPwJ6WuTkDx7Z8ynwnqlDV2q089q4OX/b/uuHgsIhIBwrouKsRQaZIqTbwNMfiqQ2zl14V0KMrTPzOiwR6Q+hqSaR5Z29WKE7ff/OWzSC3/0T6avCmcCbQaRPJdVM+QC17B0vl8FzPwRjorMngwZ0cImdQ/0Ww1d12YAL7UWp1c2egfnthKP3MuQZnNF8ixsAk1eIIwTRdiI87BOoorW8NXhxXmhyheRCsFwyP4LJBqyUVoZJ0UYyk0AO4G9EStnfpiz8YXGK+M1G4tUrWgzs1cdjlHtgCWUmITtgabnYCC4141m7n4GZTk2H/lSrJcvAs3JEiwLTj1lzeGgzeSsz/XKsnOJyzjEVr2Jp3iT+J9PbQpfS0SxTCIGgxMqllovv79pfsF/zc+vaxqSShyHW7oyn7hLMHM60LO/IIX1RWGL3rD9ecXx2pXXQ1RhIkVteIi13XkFt+KW00cstFlAd3EHCoY/XorShd2jeID7tpnYlmNfotYUs6IKefvpNC0PWkh5UXFEv3SUfw4Wd8O0DiHfhkrhxn1W/GajqSIlZ5DKgPzFg8EHexv8lSa7WJg0H3YQ=="]]
            //phpcs:enable
        ];
    }

    #[DataProvider("providerTestGetGithubKeys")]
    public function testGetGithubKeys(string $username, array $expected)
    {
        $SITE = new UnitySite();
        $this->assertEquals($expected, $SITE->getGithubKeys($username));
    }
}
