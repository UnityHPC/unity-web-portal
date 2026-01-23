<?php

class WorkerUnityCourseTest extends UnityWebPortalTestCase
{
    public function testCreateCourse()
    {
        global $LDAP, $USER;
        $this->switchUser("CourseWorkerTestManager");
        $manager = $USER;
        $pi_group_entry = $LDAP->getPIGroupEntry("pi_cs124_org1_test");
        $owner_user_entry = $LDAP->getUserEntry("cs124_org1_test");
        $this->assertFalse($pi_group_entry->exists());
        $this->assertFalse($owner_user_entry->exists());
        $stdin_file = writeLinesToTmpFile(["cs124", "Fall 2025", "cs124_org1_test", $manager->uid]);
        $stdin_file_path = getPathFromFileHandle($stdin_file);
        try {
            executeWorker("unity-course.php", stdinFilePath: $stdin_file_path);
            // error_log(implode("\n", $output_lines));
            // our LDAP conn doesn't know about changes from subprocess
            unset($GLOBALS["ldapconn"]);
            $this->switchUser("Admin");
            $pi_group_entry = $LDAP->getPIGroupEntry("pi_cs124_org1_test");
            $owner_user_entry = $LDAP->getUserEntry("cs124_org1_test");
            $this->assertTrue($pi_group_entry->exists());
            $this->assertTrue($owner_user_entry->exists());
            $this->assertEquals($manager->getMail(), $owner_user_entry->getAttribute("mail")[0]);
            $this->assertEqualsCanonicalizing(
                ["cs124_org1_test", $manager->uid],
                $pi_group_entry->getAttribute("memberuid"),
            );
            $this->assertEqualsCanonicalizing(
                [$manager->uid],
                $pi_group_entry->getAttribute("manageruid"),
            );
        } finally {
            ensurePIGroupDoesNotExist("pi_cs124_org1_test");
            ensureUserDoesNotExist("cs124_org1_test");
            unlink($stdin_file_path);
        }
    }
}
