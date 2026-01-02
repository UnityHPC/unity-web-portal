<?php

class AccountDeletionRequestTest extends UnityWebPortalTestCase
{
    public function testRequestAccountDeletionUserHasNoGroups()
    {
        global $USER, $SQL;
        $this->switchUser("Blank");
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
        $this->switchUser("NormalPI");
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
        $this->switchUser("EmptyPIGroupOwner");
        $pi_group = $USER->getPIGroup();
        $this->switchUser("Blank");
        $this->assertNumberAccountDeletionRequests(0);
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
            if ($pi_group->requestExists($USER)) {
                $pi_group->cancelGroupJoinRequest($USER);
            }
        }
    }

    public function testRequestAccountDeletionCancel()
    {
        global $USER;
        $this->switchUser("Blank");
        $this->assertNumberAccountDeletionRequests(0);
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
