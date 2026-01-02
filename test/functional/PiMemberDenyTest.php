<?php
use PHPUnit\Framework\Attributes\DataProvider;
use TRegx\PhpUnit\DataProviders\DataProvider as TRegxDataProvider;

class PIMemberDenyTest extends UnityWebPortalTestCase
{
    private function denyRequestByPI(string $uid)
    {
        http_post(__DIR__ . "/../../webroot/panel/pi.php", [
            "form_type" => "userReq",
            "action" => "Deny",
            "uid" => $uid,
        ]);
    }

    private function denyRequestByAdmin(string $uid)
    {
        global $USER;
        $gid = $USER->getPIGroup()->gid;
        $this->switchUser("Admin");
        try {
            http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
                "form_type" => "reqChild",
                "action" => "Deny",
                "pi" => $gid,
                "uid" => $uid,
            ]);
        } finally {
            $this->switchBackUser();
        }
    }

    public static function provider()
    {
        return TRegxDataProvider::list("denyRequestByPI", "denyRequestByAdmin");
    }

    #[DataProvider("provider")]
    public function testDenyRequest(string $methodName)
    {
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("Blank");
        $requestedUser = $USER;
        $this->switchUser("EmptyPIGroupOwner");
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->assertEmpty($piGroup->getRequests());
        $this->assertEqualsCanonicalizing([$pi->uid], $piGroup->getMemberUIDs());
        try {
            $piGroup->newUserRequest($requestedUser);
            $this->assertNotEmpty($piGroup->getRequests());
            call_user_func([self::class, $methodName], $requestedUser->uid);
            $this->assertEmpty($piGroup->getRequests());
            $this->assertEqualsCanonicalizing([$pi->uid], $piGroup->getMemberUIDs());
        } finally {
            $SQL->removeRequest($requestedUser->uid, $piGroup->gid);
        }
    }
}
