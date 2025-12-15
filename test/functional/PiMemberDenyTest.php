<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;
use function PHPUnit\Framework\assertEquals;

class PIMemberDenyTest extends UnityWebPortalTestCase
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
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        switchUser(...getUserIsPIHasNoMembersNoMemberRequests());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $this->assertEqualsCanonicalizing([$pi->uid], $piGroup->getMemberUIDs());
        $this->assertEmpty($piGroup->getRequests());
        $requestedUser = new UnityUser(self::$requestUid, $LDAP, $SQL, $MAILER, $WEBHOOK);
        try {
            $piGroup->newUserRequest($requestedUser);
            $this->assertFalse($piGroup->mermberUIDExists($requestedUser->uid));

            $piGroup->denyUser($requestedUser);
            $this->assertEmpty($piGroup->getRequests());
            $this->assertEqualsCanonicalizing([$pi->uid], $piGroup->getMemberUIDs());
            $this->assertFalse($piGroup->mermberUIDExists($requestedUser->uid));
        } finally {
            $SQL->removeRequest(self::$requestUid, $piGroup->gid);
        }
    }
}
