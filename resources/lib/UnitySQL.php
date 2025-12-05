<?php

namespace UnityWebPortal\lib;

use PDO;

class UnitySQL
{
    private const string TABLE_REQS = "requests";
    private const string TABLE_NOTICES = "notices";
    private const string TABLE_PAGES = "pages";
    private const string TABLE_AUDIT_LOG = "audit_log";
    private const string TABLE_ACCOUNT_DELETION_REQUESTS = "account_deletion_requests";
    // FIXME this string should be changed to something more intuitive, requires production change

    private $conn;

    public function __construct()
    {
        $this->conn = new PDO(
            "mysql:host=" . CONFIG["sql"]["host"] . ";dbname=" . CONFIG["sql"]["dbname"],
            CONFIG["sql"]["user"],
            CONFIG["sql"]["pass"],
        );
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getConn(): PDO
    {
        return $this->conn;
    }

    //
    // requests table methods
    //
    public function addRequest(string $requestor, string $dest): void
    {
        if ($this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_REQS . " (uid, request_for) VALUES (:uid, :request_for)",
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);
        $stmt->execute();
    }

    public function removeRequest($requestor, string $dest): void
    {
        if (!$this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for",
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function removeRequests(string $dest): void
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE request_for=:request_for",
        );
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function getRequest(string $user, string $dest): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for",
        );
        $stmt->bindParam(":uid", $user);
        $stmt->bindParam(":request_for", $dest);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if (count($result) == 0) {
            throw new \Exception("no such request: uid='$user' request_for='$dest'");
        }
        if (count($result) > 1) {
            throw new \Exception("multiple requests for uid='$user' request_for='$dest'");
        }
        return $result[0];
    }

    public function requestExists(string $requestor, string $dest): bool
    {
        try {
            $this->getRequest($requestor, $dest);
            return true;
            // FIXME use a specific exception
        } catch (\Exception) {
            return false;
        }
    }

    public function getAllRequests(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_REQS);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRequests(string $dest): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE request_for=:request_for",
        );
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRequestsByUser(string $user): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid");
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function deleteRequestsByUser(string $user): void
    {
        $stmt = $this->conn->prepare("DELETE FROM " . self::TABLE_REQS . " WHERE uid=:uid");
        $stmt->bindParam(":uid", $user);

        $stmt->execute();
    }

    public function addNotice(
        string $title,
        string $date,
        string $content,
        UnityUser $operator,
    ): void {
        $table = self::TABLE_NOTICES;
        $stmt = $this->conn->prepare(
            "INSERT INTO $table (date, title, message) VALUES (:date, :title, :message)",
        );
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $content);

        $stmt->execute();

        $this->addLog($operator->uid, $_SERVER["REMOTE_ADDR"], "added_cluster_notice", $operator);
    }

    public function editNotice(string $id, string $title, string $date, string $content): void
    {
        $table = self::TABLE_NOTICES;
        $stmt = $this->conn->prepare(
            "UPDATE $table SET date=:date, title=:title, message=:message WHERE id=:id",
        );
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $content);
        $stmt->bindParam(":id", $id);

        $stmt->execute();
    }

    public function deleteNotice(string $id): void
    {
        $stmt = $this->conn->prepare("DELETE FROM " . self::TABLE_NOTICES . " WHERE id=:id");
        $stmt->bindParam(":id", $id);

        $stmt->execute();
    }

    public function getNotice(string $id): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_NOTICES . " WHERE id=:id");
        $stmt->bindParam(":id", $id);

        $stmt->execute();

        return $stmt->fetchAll()[0];
    }

    public function getNotices(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_NOTICES . " ORDER BY date DESC",
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPages(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_PAGES);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPage(string $id): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_PAGES . " WHERE page=:id");
        $stmt->bindParam(":id", $id);

        $stmt->execute();

        return $stmt->fetchAll()[0];
    }

    public function editPage(string $id, string $content, UnityUser $operator): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_PAGES . " SET content=:content WHERE page=:id",
        );
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":content", $content);

        $stmt->execute();

        $this->addLog($operator->uid, $_SERVER["REMOTE_ADDR"], "edited_page", $operator);
    }

    public function addLog(
        string $operator,
        string $operator_ip,
        string $action_type,
        string $recipient,
    ): void {
        $table = self::TABLE_AUDIT_LOG;
        $stmt = $this->conn->prepare(
            "INSERT INTO $table (operator, operator_ip, action_type, recipient)
            VALUE (:operator, :operator_ip, :action_type, :recipient)",
        );
        $stmt->bindParam(":operator", $operator);
        $stmt->bindParam(":operator_ip", $operator_ip);
        $stmt->bindParam(":action_type", $action_type);
        $stmt->bindParam(":recipient", $recipient);

        $stmt->execute();
    }

    public function addAccountDeletionRequest(string $uid): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_ACCOUNT_DELETION_REQUESTS . " (uid) VALUE (:uid)",
        );
        $stmt->bindParam(":uid", $uid);

        $stmt->execute();
    }

    public function accDeletionRequestExists(string $uid): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_ACCOUNT_DELETION_REQUESTS . " WHERE uid=:uid",
        );
        $stmt->bindParam(":uid", $uid);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function deleteAccountDeletionRequest(string $uid): void
    {
        if (!$this->accDeletionRequestExists($uid)) {
            return;
        }
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_ACCOUNT_DELETION_REQUESTS . " WHERE uid=:uid",
        );
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
    }
}
