<?php

use PHPUnit\Framework\TestCase;
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
        switchUser(...getUserIsPIHasAtLeastOneMember());
        $pi = $USER;
        $piUid = $USER->uid;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $memberUIDs = $piGroup->getGroupMemberUIDs();
        // the 0th member of the PI group is the PI
        $this->assertGreaterThan(1, count($memberUIDs));
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
        $this->assertTrue($piGroup->memberExists($memberToDelete));
        try {
            $this->removeUser($memberToDelete->uid);
            $this->assertFalse($piGroup->memberExists($memberToDelete));
        } finally {
            if (!$piGroup->memberExists($memberToDelete)) {
                $piGroup->newUserRequest($memberToDelete);
                $piGroup->approveUser($memberToDelete);
            }
        }
    }

    public function testRemovePIFromTheirOwnGroup()
    {
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        switchUser(...getUserIsPIHasAtLeastOneMember());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $this->assertTrue($piGroup->memberExists($pi));
        $this->expectException(Exception::class);
        try {
            $this->removeUser($pi->uid);
            $this->assertTrue($piGroup->memberExists($pi));
        } finally {
            if (!$piGroup->memberExists($pi)) {
                $piGroup->newUserRequest($pi);
                $piGroup->approveUser($pi);
            }
        }
    }
}
