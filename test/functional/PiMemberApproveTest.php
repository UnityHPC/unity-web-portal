<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityGroup;
use UnityWebPortal\lib\UnitySSO;

class PiMemberApproveTest extends TestCase
{
    static $userWithRequestSwitchArgs;
    static $userWithoutRequestSwitchArgs;
    static $piSwitchArgs;
    static $pi;
    static $userWithRequestUID;
    static $userWithoutRequestUID;
    static $piUID;
    static $userWithRequest;
    static $userWithoutRequest;
    static $piGroup;
    static $piGroupGID;

    private function approveUser(string $uid)
    {
        http_post(__DIR__ . "/../../webroot/panel/pi.php", [
            "form_type" => "userReq",
            "action" => "Approve",
            "uid" => $uid,
        ]);
    }

    private function requestJoinPI(string $gid)
    {
        http_post(__DIR__ . "/../../webroot/panel/groups.php", [
            "form_type" => "addPIform",
            "pi" => $gid,
            "tos" => "agree",
        ]);
    }

    private function assertGroupMembers(UnityGroup $group, array $members)
    {
        assert(arraysAreEqualUnOrdered($members, $group->getGroupMemberUIDs()));
    }

    public function testApproveRequest()
    {
        global $USER;
        $userSwitchArgs = getNormalUser();
        $piSwitchArgs = getUserIsPIHasNoMembersNoMemberRequests();
        switchUser(...$userSwitchArgs);
        $user = $USER;
        $uid = $USER->uid;
        switchUser(...$piSwitchArgs);
        $piUID = $USER->uid;
        $piGroup = $USER->getPIGroup();

        assert($piGroup->exists());
        $this->assertGroupMembers($piGroup, [$piUID]);
        $this->assertEmpty($piGroup->getRequests());
        try {
            switchUser(...$userSwitchArgs);
            $this->requestJoinPI($piGroup->gid);
            $this->assertFalse($piGroup->memberExists($user));

            switchUser(...$piSwitchArgs);
            $this->approveUser($uid);
            assert(!$piGroup->requestExists($user));
            $this->assertEmpty($piGroup->getRequests());
            $this->assertGroupMembers($piGroup, [$piUID, $uid]);
            assert($piGroup->memberExists($user));
        } finally {
            if ($piGroup->memberExists($user)) {
                $piGroup->removeUser($user);
            }
            if ($piGroup->requestExists($user)) {
                $piGroup->cancelGroupJoinRequest($user);
            }
        }
    }

    public function testApproveNonexistentRequest()
    {
        global $USER;
        switchUser(...getNormalUser2());
        $user = $USER;
        $uid = $USER->uid;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $piUID = $USER->uid;
        $piGroup = $USER->getPIGroup();

        assert($piGroup->exists());
        $this->assertGroupMembers($piGroup, [$piUID]);
        $this->assertEmpty($piGroup->getRequests());
        $this->assertFalse($piGroup->memberExists($user));
        $this->assertEmpty($piGroup->getRequests());
        try {
            $this->expectException(Exception::class);
            $piGroup->approveUser($user);
        } finally {
            if ($piGroup->memberExists($user)) {
                $piGroup->removeUser($user);
            }
        }
    }
}
