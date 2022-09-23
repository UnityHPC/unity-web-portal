<?php

namespace UnityWebPortal\lib;

use PDO;

class UnitySQL
{
    private const TABLE_REQS = "requests";
    private const TABLE_NOTICES = "notices";
    private const TABLE_SSOLOG = "sso_log";
    private const TABLE_PAGES = "pages";
    private const TABLE_EVENTS = "events";

    private const REQUEST_ADMIN = "admin";

    private $conn;

    public function __construct($db_host, $db, $db_user, $db_pass)
    {
        $this->conn = new PDO("mysql:host=" . $db_host . ";dbname=" . $db, $db_user, $db_pass);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getConn()
    {
        return $this->conn;
    }

    //
    // requests table methods
    //
    public function addRequest($requestor, $dest = self::REQUEST_ADMIN)
    {
        if ($this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_REQS . " (uid, request_for) VALUES (:uid, :request_for)"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function removeRequest($requestor, $dest = self::REQUEST_ADMIN)
    {
        if (!$this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function removeRequests($dest = self::REQUEST_ADMIN)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE request_for=:request_for"
        );
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function requestExists($requestor, $dest = self::REQUEST_ADMIN)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function getRequests($dest = self::REQUEST_ADMIN)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE request_for=:request_for"
        );
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRequestsByUser($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function deleteRequestsByUser($user)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();
    }

    public function addSSOEntry($uid, $org, $firstname, $lastname, $mail)
    {
        if ($this->getStoredUID($uid) == null) {
            $stmt = $this->conn->prepare(
                "INSERT INTO " . self::TABLE_SSOLOG . " (uid, org, firstname, lastname, mail) 
                VALUES (:uid, :org, :firstname, :lastname, :mail)"
            );
            $stmt->bindParam(":uid", $uid);
            $stmt->bindParam(":org", $org);
            $stmt->bindParam(":firstname", $firstname);
            $stmt->bindParam(":lastname", $lastname);
            $stmt->bindParam(":mail", $mail);

            $stmt->execute();
        }
    }

    public function getStoredUID($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT uid FROM " . self::TABLE_SSOLOG . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        $result = $stmt->fetchAll();
        if (count($result) > 0) {
            return $result[0]["uid"];
        } else {
            return null;
        }
    }

    public function getStoredOrg($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT org FROM " . self::TABLE_SSOLOG . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        $result = $stmt->fetchAll();
        if (count($result) > 0) {
            return $result[0]["org"];
        } else {
            return null;
        }
    }

    public function getStoredFirstname($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT firstname FROM " . self::TABLE_SSOLOG . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        $result = $stmt->fetchAll();
        if (count($result) > 0) {
            return $result[0]["firstname"];
        } else {
            return null;
        }
    }

    public function getStoredLastname($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT lastname FROM " . self::TABLE_SSOLOG . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        $result = $stmt->fetchAll();
        if (count($result) > 0) {
            return $result[0]["lastname"];
        } else {
            return null;
        }
    }

    public function getStoredMail($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT mail FROM " . self::TABLE_SSOLOG . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $user);

        $stmt->execute();

        $result = $stmt->fetchAll();
        if (count($result) > 0) {
            return $result[0]["mail"];
        } else {
            return null;
        }
    }

    public function addNotice($title, $date, $content)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_NOTICES . " (date, title, message) VALUES (:date, :title, :message)"
        );
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $content);

        $stmt->execute();
    }

    public function editNotice($id, $title, $date, $content)
    {
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_NOTICES . " SET date=:date, title=:title, message=:message WHERE id=:id"
        );
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $content);
        $stmt->bindParam(":id", $id);

        $stmt->execute();
    }

    public function deleteNotice($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_NOTICES . " WHERE id=:id"
        );
        $stmt->bindParam(":id", $id);

        $stmt->execute();
    }

    public function getNotice($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_NOTICES . " WHERE id=:id"
        );
        $stmt->bindParam(":id", $id);

        $stmt->execute();

        return $stmt->fetchAll()[0];
    }

    public function getNotices()
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_NOTICES . " ORDER BY date DESC"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPages()
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_PAGES
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getPage($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_PAGES . " WHERE page=:id"
        );
        $stmt->bindParam(":id", $id);

        $stmt->execute();

        return $stmt->fetchAll()[0];
    }

    public function editPage($id, $content)
    {
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_PAGES . " SET content=:content WHERE page=:id"
        );
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":content", $content);

        $stmt->execute();
    }

    public function addEvent($operator, $action, $entity)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_EVENTS . " (operator, action, entity) VALUE (:operator, :action, :entity)"
        );
        $stmt->bindParam(":operator", $operator);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":entity", $entity);

        $stmt->execute();
    }
}
