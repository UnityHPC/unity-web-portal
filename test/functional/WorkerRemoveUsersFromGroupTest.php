<?php

use UnityWebPortal\lib\UnityUser;
use UnityWebPortal\lib\UnityLDAP;

class WorkerRemoveUsersFromGroupTest extends UnityWebPortalTestCase
{
    private function writeLinesToTmpFile(array $lines)
    {
        $file = tmpfile();
        if (!$file) {
            throw new RuntimeException("failed to make tmpfile");
        }
        $path = stream_get_meta_data($file)["uri"];
        $contents = implode("\n", $lines);
        $fwrite = fwrite($file, $contents);
        if ($fwrite === false) {
            throw new RuntimeException("failed to write to tmpfile '$path'");
        }
        return $file;
    }

    public function testRemoveUsersFromGroup()
    {
        global $USER, $LDAP, $SQL, $MAILER, $WEBHOOK;
        $this->switchUser("EmptyPIGroupOwner");
        $pi = $USER;
        $pi_group = $USER->getPIGroup();
        $this->assertTrue($pi->isPI());
        $this->assertEqualsCanonicalizing([$pi->uid], $pi_group->getMemberUIDs());
        $this->assertEqualsCanonicalizing([$pi->uid], $pi_group->getMemberUIDs());
        $this->assertEqualsCanonicalizing([], $pi_group->getRequests());
        $uids = getSomeUIDsOfQualifiedUsersNotRequestedAccountDeletion();
        $uids_to_remove = array_slice($uids, 0, 3);
        $expected_new_uids = array_diff(array_merge([$pi->uid], $uids), $uids_to_remove);
        $remove_uids_file = $this->writeLinesToTmpFile($uids_to_remove);
        $remove_uids_file_path = stream_get_meta_data($remove_uids_file)["uri"];
        try {
            foreach ($uids as $uid) {
                $user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
                $pi_group->newUserRequest($user, false);
                $pi_group->approveUser($user, false);
            }
            [$_, $output] = executeWorker(
                "remove-users-from-group.php",
                "$pi_group->gid $remove_uids_file_path",
            );
            print implode("\n", $output);
            // our $LDAP is not aware of changes made by worker subprocess, so throw it out
            unset($GLOBALS["ldapconn"]);
            $this->switchUser("EmptyPIGroupOwner", validate: false);
            $pi = $USER;
            $pi_group = $USER->getPIGroup();
            $this->assertEqualsCanonicalizing($expected_new_uids, $pi_group->getMemberUIDs());
            $this->assertEqualsCanonicalizing($expected_new_uids, $pi_group->getMemberUIDs());
        } finally {
            foreach ($uids as $uid) {
                $user = new UnityUser($uid, $LDAP, $SQL, $MAILER, $WEBHOOK);
                $pi_group->removeUser($user);
            }
        }
    }
}
