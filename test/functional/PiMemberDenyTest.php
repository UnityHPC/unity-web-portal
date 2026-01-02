<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;
use function PHPUnit\Framework\assertEquals;

class PIMemberDenyTest extends UnityWebPortalTestCase
{
    static $requestUid;

    public function setUp(): void
    {
        parent::setUp();
        global $USER;
        $this->switchUser("Normal");
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
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("EmptyPIGroupOwner");
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $requestedUser = new UnityUser(self::$requestUid, $LDAP, $SQL, $MAILER, $WEBHOOK);
        try {
            $piGroup->newUserRequest($requestedUser);
            $this->assertFalse($piGroup->memberUIDExists($requestedUser->uid));

            $piGroup->denyUser($requestedUser);
            $this->assertEmpty($piGroup->getRequests());
            $this->assertEqualsCanonicalizing([$pi->uid], $piGroup->getMemberUIDs());
            $this->assertFalse($piGroup->memberUIDExists($requestedUser->uid));
        } finally {
            $SQL->removeRequest(self::$requestUid, $piGroup->gid);
        }
    }
}
