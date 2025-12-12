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
}
