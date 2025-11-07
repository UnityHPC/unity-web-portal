<?php

namespace UnityWebPortal\lib;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UnityGithubTest extends TestCase
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
            [
                "simonLeary42",
                [
                    "ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBLeHpW10CCamJtXNXJui49WM07wRnQbQTbQ2MSvF4j8vBpBuAbjiEp14qERLDs3FoWdpbiUwL9mZq6PmUSxaTnk=",
                ],
            ],
        ];
    }

    #[DataProvider("providerTestGetGithubKeys")]
    public function testGetGithubKeys(string $username, array $expected)
    {
        $GITHUB = new UnityGithub();
        $this->assertEquals($expected, $GITHUB->getSshPublicKeys($username));
    }
}
