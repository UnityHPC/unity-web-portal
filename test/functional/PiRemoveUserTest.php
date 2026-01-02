<?php

use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;

class PIRemoveUserTest extends UnityWebPortalTestCase
{
    private function removeUser(string $uid)
    {
        http_post(__DIR__ . "/../../webroot/panel/pi.php", [
            "form_type" => "remUser",
            "uid" => $uid,
        ]);
    }

    public function testRemoveUser()
    {
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("NormalPI");
        $pi = $USER;
        $piUid = $USER->uid;
        $piGroup = $USER->getPIGroup();
        $memberUIDs = $piGroup->getMemberUIDs();
        // the ordering of the uids in getGroupMemberUIDs is different each time
        // use a linear search to find a user who is not the PI
        $memberToDelete = null;
        foreach ($memberUIDs as $uid) {
            if ($uid != $piUid) {
                $memberToDelete = new UnityUser($uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
                if ($memberToDelete->hasRequestedAccountDeletion()) {
                    continue;
                }
                break;
            }
        }
        $this->assertNotEquals($pi->uid, $memberToDelete->uid);
        $this->assertTrue($piGroup->memberUIDExists($memberToDelete->uid));
        try {
            $this->removeUser($memberToDelete->uid);
            $this->assertFalse($piGroup->memberUIDExists($memberToDelete->uid));
        } finally {
            if (!$piGroup->memberUIDExists($memberToDelete->uid)) {
                $piGroup->newUserRequest($memberToDelete);
                $piGroup->approveUser($memberToDelete);
            }
        }
    }

    public function testRemovePIFromTheirOwnGroup()
    {
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("NormalPI");
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->expectException(Exception::class);
        try {
            $this->removeUser($pi->uid);
            $this->assertTrue($piGroup->memberUIDExists($pi->uid));
        } finally {
            if (!$piGroup->memberUIDExists($pi->uid)) {
                $piGroup->newUserRequest($pi);
                $piGroup->approveUser($pi);
            }
        }
    }
}
