<?php

class WorkerUnityCourseTest extends UnityWebPortalTestCase
{
    private static string $course_id = "cs124";
    private static string $course_org = "org1_test";
    private static string $course_owner_uid = "cs124_org1_test";
    private static string $course_gid = "pi_cs124_org1_test";
    private static string $course_semester = "Fall 2025";
    private static string $test1_manager_uid = "user2_org1_test";
    private static array $test2_manager_uids = ["user2_org1_test", "user1_org1_test"];
    private static string $test2_manager_uids_str = " user2_org1_test , user1_org1_test , ,,, user2_org1_test ";
    private static string $manager_mail = "user2@org1.test";
    private static string $courseOwnerMail = "user2+cs124@org1.test";

    public function testCreateCourse()
    {
        global $LDAP, $USER;
        $this->switchUser("Blank");
        $this->assertEquals(self::$test1_manager_uid, $USER->uid);
        $this->assertEquals(self::$manager_mail, $USER->getMail());
        $manager = $USER;
        $pi_group_entry = $LDAP->getPIGroupEntry(self::$course_gid);
        $owner_user_entry = $LDAP->getUserEntry(self::$course_owner_uid);
        $this->assertFalse($pi_group_entry->exists());
        $this->assertFalse($owner_user_entry->exists());
        $stdin_file = writeLinesToTmpFile([
            self::$course_id,
            self::$course_semester,
            self::$course_org,
            self::$test1_manager_uid,
        ]);
        $stdin_file_path = getPathFromFileHandle($stdin_file);
        try {
            executeWorker("unity-course.php", stdinFilePath: $stdin_file_path);
            // error_log(implode("\n", $output_lines));
            $this->switchUser("Admin");
            $pi_group_entry = $LDAP->getPIGroupEntry(self::$course_gid);
            $owner_user_entry = $LDAP->getUserEntry(self::$course_owner_uid);
            $this->assertTrue($pi_group_entry->exists());
            $this->assertTrue($owner_user_entry->exists());
            $this->assertEquals(self::$courseOwnerMail, $owner_user_entry->getAttribute("mail")[0]);
            $this->assertEqualsCanonicalizing(
                [self::$course_owner_uid, $manager->uid],
                $pi_group_entry->getAttribute("memberuid"),
            );
            $this->assertEqualsCanonicalizing(
                [$manager->uid],
                $pi_group_entry->getAttribute("manageruid"),
            );
        } finally {
            ensurePIGroupDoesNotExist(self::$course_gid);
            ensureUserDoesNotExist(self::$course_owner_uid);
            unlink($stdin_file_path);
        }
    }

    public function testCreateCourseMultipleManagers()
    {
        global $LDAP, $USER;
        $this->switchUser("Blank");
        $pi_group_entry = $LDAP->getPIGroupEntry(self::$course_gid);
        $owner_user_entry = $LDAP->getUserEntry(self::$course_owner_uid);
        $this->assertFalse($pi_group_entry->exists());
        $this->assertFalse($owner_user_entry->exists());
        $stdin_file = writeLinesToTmpFile([
            self::$course_id,
            self::$course_semester,
            self::$course_org,
            self::$test2_manager_uids_str,
        ]);
        $stdin_file_path = getPathFromFileHandle($stdin_file);
        try {
            executeWorker("unity-course.php", stdinFilePath: $stdin_file_path);
            // error_log(implode("\n", $output_lines));
            $this->switchUser("Admin");
            $pi_group_entry = $LDAP->getPIGroupEntry(self::$course_gid);
            $owner_user_entry = $LDAP->getUserEntry(self::$course_owner_uid);
            $this->assertTrue($pi_group_entry->exists());
            $this->assertTrue($owner_user_entry->exists());
            $this->assertEquals(self::$courseOwnerMail, $owner_user_entry->getAttribute("mail")[0]);
            $this->assertEqualsCanonicalizing(
                array_merge([self::$course_owner_uid], self::$test2_manager_uids),
                $pi_group_entry->getAttribute("memberuid"),
            );
            $this->assertEqualsCanonicalizing(
                self::$test2_manager_uids,
                $pi_group_entry->getAttribute("manageruid"),
            );
        } finally {
            ensurePIGroupDoesNotExist(self::$course_gid);
            ensureUserDoesNotExist(self::$course_owner_uid);
            unlink($stdin_file_path);
        }
    }
}
