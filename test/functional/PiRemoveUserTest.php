<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnityWebPortal\lib\UnityUser;

class PiRemoveUserTest extends TestCase {
    private function removeUser(string $uid)
    {
        http_post(
            __DIR__ . "/../../webroot/panel/pi.php",
            ["form_name" => "remUser", "uid" => $uid]
        );
    }

    public function testRemoveUser()
    {
        global $USER, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasAtLeastOneMember());
        $pi = $USER;
        $piUid = $USER->getUID();
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
                $memberToDelete = new UnityUser($uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK);
                if ($memberToDelete->hasRequestedAccountDeletion()) {
                    continue;
                }
                break;
            }
        }
        $this->assertNotEquals($pi->getUID(), $memberToDelete->getUID());
        $this->assertTrue($piGroup->userExists($memberToDelete));
        try {
            $this->removeUser($memberToDelete->getUID());
            $this->assertFalse($piGroup->userExists($memberToDelete));
        } finally {
            if (!$piGroup->userExists($memberToDelete)) {
                $piGroup->newUserRequest($memberToDelete);
                $piGroup->approveUser($memberToDelete);
            }
        }
    }

    public function testRemovePIFromTheirOwnGroup()
    {
        global $USER, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK;
        switchUser(...getUserIsPIHasAtLeastOneMember());
        $pi = $USER;
        $piGroup = $USER->getPIGroup();
        $this->assertTrue($piGroup->exists());
        $this->assertTrue($piGroup->userExists($pi));
        $this->expectException(Exception::class);
        try {
            $this->removeUser($pi->getUID());
            $this->assertTrue($piGroup->userExists($pi));
        } finally {
            if (!$piGroup->userExists($pi)) {
                $piGroup->newUserRequest($pi);
                $piGroup->approveUser($pi);
            }
       }
    }
}
