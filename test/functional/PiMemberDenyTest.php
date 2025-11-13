<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;

class PiMemberDenyTest extends TestCase
{
    static $requestUid;

    public static function setUpBeforeClass(): void
    {
        global $USER;
        switchUser(...getNormalUser());
        self::$requestUid = $USER->uid;
    }

    private function denyUser(string $uid)
    {
        http_post(__DIR__ . "/../../webroot/panel/pi.php", [
            "form_type" => "userReq",
            "action" => "approve",
            "uid" => $uid,
        ]);
    }

    public function testDenyRequest()
    {
        global $USER, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        assert($piGroup->exists());
        assert(arraysAreEqualUnOrdered([$pi->uid], $piGroup->getGroupMemberUIDs()));
        $this->assertEmpty($piGroup->getRequests());
        $requestedUser = new UnityUser(self::$requestUid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
        try {
            $piGroup->newUserRequest(
                $requestedUser,
                $requestedUser->getFirstname(),
                $requestedUser->getLastname(),
                $requestedUser->getMail(),
                $requestedUser->getOrg(),
            );
            $this->assertFalse($piGroup->memberExists($requestedUser));

            $piGroup->denyUser($requestedUser);
            $this->assertEmpty($piGroup->getRequests());
            assert(arraysAreEqualUnOrdered([$pi->uid], $piGroup->getGroupMemberUIDs()));
            $this->assertFalse($piGroup->memberExists($requestedUser));
        } finally {
            $SQL->removeRequest(self::$requestUid, $piGroup->gid);
        }
    }
}
