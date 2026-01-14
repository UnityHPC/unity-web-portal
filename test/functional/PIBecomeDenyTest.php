<?php
use UnityWebPortal\lib\UnitySQL;

class PIBecomeDenyTest extends UnityWebPortalTestCase
{
    public function testDenyPiBecomeRequest()
    {
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("Blank");
        $piGroup = $USER->getPIGroup();
        $this->assertFalse($piGroup->exists());
        $this->assertFalse($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI));
        $piGroup->requestGroup();
        try {
            $this->assertTrue($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI));
            $this->switchUser("Admin");
            http_post(__DIR__ . "/../../webroot/admin/pi-mgmt.php", [
                "form_type" => "req",
                "action" => "Deny",
                "uid" => $piGroup->getOwner()->uid,
            ]);
            $this->switchBackUser();
            $this->assertFalse($piGroup->exists());
            $this->assertFalse($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI));
        } finally {
            if ($SQL->requestExists($USER->uid, UnitySQL::REQUEST_BECOME_PI)) {
                $SQL->removeRequest($USER->uid, UnitySQL::REQUEST_BECOME_PI);
            }
        }
    }
}
