<?php

use PHPUnit\Framework\TestCase;

class AccountDeletionRequestTest extends TestCase
{
    private function assertNumberAccountDeletionRequests(int $x)
    {
        global $USER, $SQL;
        if ($x == 0) {
            $this->assertFalse($USER->hasRequestedAccountDeletion());
            $this->assertFalse($SQL->accDeletionRequestExists($USER->uid));
        } elseif ($x > 0) {
            $this->assertTrue($USER->hasRequestedAccountDeletion());
            $this->assertTrue($SQL->accDeletionRequestExists($USER->uid));
        } else {
            throw new RuntimeError("x must not be negative");
        }
        $this->assertEquals($x, $this->getNumberAccountDeletionRequests());
    }

    private function getNumberAccountDeletionRequests()
    {
        global $USER, $SQL;
        $stmt = $SQL->getConn()->prepare(
            "SELECT * FROM account_deletion_requests WHERE uid=:uid"
        );
        $uid = $USER->uid;
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
        return count($stmt->fetchAll());
    }

    public function testRequestAccountDeletionUserHasNoGroups()
    {
        global $USER, $SQL;
        switchUser(...getUserHasNotRequestedAccountDeletionHasNoGroups());
        $this->assertEmpty($USER->getGroups());
        $this->assertNumberAccountDeletionRequests(0);
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/account.php",
                ["form_type" => "account_deletion_request"]
            );
            $this->assertNumberAccountDeletionRequests(1);
            http_post(
                __DIR__ . "/../../webroot/panel/account.php",
                ["form_type" => "account_deletion_request"]
            );
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
        $this->assertNotEmpty($USER->getGroups());
        $this->assertNumberAccountDeletionRequests(0);
        try {
            http_post(
                __DIR__ . "/../../webroot/panel/account.php",
                ["form_type" => "account_deletion_request"]
            );
            $this->assertNumberAccountDeletionRequests(0);
        } finally {
            $SQL->deleteAccountDeletionRequest($USER->uid);
            $this->assertNumberAccountDeletionRequests(0);
        }
    }
}
