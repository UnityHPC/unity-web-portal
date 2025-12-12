<?php

class AccountDeletionRequestTest extends UnityWebPortalTestCase
{
    public function testRequestAccountDeletionUserHasNoGroups()
    {
        global $USER, $SQL;
        switchUser(...getBlankUser());
        $this->assertEmpty($USER->getPIGroupGIDs());
        $this->assertNumberAccountDeletionRequests(0);
        try {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "account_deletion_request",
            ]);
            $this->assertNumberAccountDeletionRequests(1);
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "account_deletion_request",
            ]);
            $this->assertNumberAccountDeletionRequests(1);
        } finally {
            $SQL->deleteAccountDeletionRequest($USER->uid);
            $this->assertNumberAccountDeletionRequests(0);
        }
    }

    public function testRequestAccountDeletionUserHasGroup()
    {
        // FIXME this should be an error
        global $USER, $SQL;
        switchUser(...getUserHasNotRequestedAccountDeletionHasGroup());
        $this->assertNotEmpty($USER->getPIGroupGIDs());
        $this->assertNumberAccountDeletionRequests(0);
        try {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "account_deletion_request",
            ]);
            $this->assertNumberAccountDeletionRequests(0);
        } finally {
            $SQL->deleteAccountDeletionRequest($USER->uid);
            $this->assertNumberAccountDeletionRequests(0);
        }
    }

    /* when you request account deletion, any other requests should be deleted */
    public function testRequestAccountDeletionUserHasRequest()
    {
        global $USER, $SQL;
        $pi_args = getUserIsPIHasNoMembersNoMemberRequests();
        switchUser(...$pi_args);
        $pi = $USER;
        $pi_group = $USER->getPIGroup();
        $this->assertEqualsCanonicalizing([$pi->uid], $pi_group->getGroupMemberUIDs());
        $user_args = getBlankUser();
        switchUser(...$user_args);
        $this->assertEmpty($USER->getPIGroupGIDs());
        $this->assertNumberAccountDeletionRequests(0);
        $this->assertNumberRequests(0);
        try {
            $pi_group->newUserRequest($USER);
            $this->assertNumberRequests(1);
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "account_deletion_request",
            ]);
            $this->assertNumberAccountDeletionRequests(1);
            $this->assertNumberRequests(0);
        } finally {
            $SQL->deleteAccountDeletionRequest($USER->uid);
            $this->assertNumberAccountDeletionRequests(0);
            ensureUserNotInPIGroup($pi_group);
        }
    }

    public function testRequestAccountDeletionCancel()
    {
        global $USER;
        switchUser(...getBlankUser());
        $this->assertEmpty($USER->getPIGroupGIDs());
        $this->assertNumberAccountDeletionRequests(0);
        $this->assertNumberRequests(0);
        try {
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "account_deletion_request",
            ]);
            $this->assertNumberAccountDeletionRequests(1);
            http_post(__DIR__ . "/../../webroot/panel/account.php", [
                "form_type" => "cancel_account_deletion_request",
            ]);
            $this->assertNumberAccountDeletionRequests(0);
        } finally {
            ensureUserNotRequestedAccountDeletion();
        }
    }
}
