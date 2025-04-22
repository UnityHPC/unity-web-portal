<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UnitySiteTest extends TestCase
{
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
        $GITHUB = new UnityGithub();
        $this->assertEquals($expected, $GITHUB->getSshPublicKeys($username));
    }
}
