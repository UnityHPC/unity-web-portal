<?php
class PIMemberDenyTest extends UnityWebPortalTestCase
{
    public function testDenyRequest()
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
            http_post(__DIR__ . "/../../webroot/panel/pi.php", [
                "form_type" => "userReq",
                "action" => "Deny",
                "uid" => $requestedUser->uid,
            ]);
            $this->assertEmpty($piGroup->getRequests());
            $this->assertEqualsCanonicalizing([$pi->uid], $piGroup->getMemberUIDs());
        } finally {
            $SQL->removeRequest($requestedUser->uid, $piGroup->gid);
        }
    }
}
