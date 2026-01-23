<?php

class WorkerUnityCourseTest extends UnityWebPortalTestCase
{
    public function testCreateCourse()
    {
        global $LDAP;
        $this->switchUser("Admin");
        $pi_group_entry = $LDAP->getPIGroupEntry("pi_cs124_org1_test");
        $owner_user_entry = $LDAP->getUserEntry("cs124_org1_test");
        $this->assertFalse($pi_group_entry->exists());
        $this->assertFalse($owner_user_entry->exists());
        $stdin_file = writeLinesToTmpFile([
            "cs124",
            "Fall 2025",
            "cs124_org1_test",
            "user1_org1_test",
        ]);
        $stdin_file_path = getPathFromFileHandle($stdin_file);
        try {
            [$rc, $output_lines] = executeWorker(
                "unity-course.php",
                stdinFilePath: $stdin_file_path,
            );
            // error_log(implode("\n", $output_lines));
            // our LDAP conn doesn't know about changes from subprocess
            unset($GLOBALS["ldapconn"]);
            $this->switchUser("Admin");
            $pi_group_entry = $LDAP->getPIGroupEntry("pi_cs124_org1_test");
            $owner_user_entry = $LDAP->getUserEntry("cs124_org1_test");
            $this->assertTrue($pi_group_entry->exists());
            $this->assertTrue($owner_user_entry->exists());
            $this->assertEquals("user1@org1.test", $owner_user_entry->getAttribute("mail")[0]);
            $this->assertEqualsCanonicalizing(
                ["cs124_org1_test", "user1_org1_test"],
                $pi_group_entry->getAttribute("memberuid"),
            );
        } finally {
            ensurePIGroupDoesNotExist("pi_cs124_org1_test");
            ensureUserDoesNotExist("cs124_org1_test");
            unlink($stdin_file_path);
        }
    }
}
