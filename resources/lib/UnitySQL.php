<?php

class UnitySQL
{

    const TABLE_REQS = "requests";
    const TABLE_NOTICES = "notices";

    const REQUEST_ADMIN = "admin";

    private $conn;

    public function __construct($db_host, $db, $db_user, $db_pass)
    {
        $this->conn = new PDO("mysql:host=" . $db_host . ";dbname=" . $db, $db_user, $db_pass);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getConn() {
        return $this->conn;
    }

    //
    // requests table methods
    //
    public function addRequest($requestor, $dest = self::REQUEST_ADMIN) {
        if ($this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare("INSERT INTO " . self::TABLE_REQS . " (uid, request_for) VALUES (:uid, :request_for)");
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function removeRequest($requestor, $dest = self::REQUEST_ADMIN) {
        if (!$this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare("DELETE FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for");
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function removeRequests($dest = self::REQUEST_ADMIN) {
        $stmt = $this->conn->prepare("DELETE FROM " . self::TABLE_REQS . " WHERE request_for=:request_for");
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function requestExists($requestor, $dest = self::REQUEST_ADMIN) {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for");
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function getRequests($dest = self::REQUEST_ADMIN) {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_REQS . " WHERE request_for=:request_for");
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRequestsByUser($user) {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid");
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getNotices() {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_NOTICES . " ORDER BY date DESC");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
