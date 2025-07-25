<?php

namespace UnityWebPortal\lib;

use PDO;

class UnitySQL
{
    private const TABLE_REQS = "requests";
    private const TABLE_NOTICES = "notices";
    private const TABLE_PAGES = "pages";
    private const TABLE_AUDIT_LOG = "audit_log";
    private const TABLE_ACCOUNT_DELETION_REQUESTS = "account_deletion_requests";
    private const TABLE_SITEVARS = "sitevars";
    private const TABLE_GROUP_ROLES = "groupRoles";
    private const TABLE_GROUP_TYPES = "groupTypes";
    private const TABLE_GROUP_ROLE_ASSIGNMENTS = "groupRoleAssignments";
    private const TABLE_GROUP_REQUESTS = "groupRequests";
    private const TABLE_GROUP_JOIN_REQUESTS = "groupJoinRequests";


    // FIXME this string should be changed to something more intuitive, requires production sql change
    public const REQUEST_BECOME_PI = "admin";

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
    public function addRequest($requestor, $firstname, $lastname, $email, $org, $dest = self::REQUEST_BECOME_PI)
    {
        if ($this->requestExists($requestor, $dest)) {
            return;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_REQS . " " .
            "(uid, firstname, lastname, email, org, request_for) VALUES " .
            "(:uid, :firstname, :lastname, :email, :org, :request_for)"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $dest);
        $stmt->bindParam(":firstname", $firstname);
        $stmt->bindParam(":lastname", $lastname);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":org", $org);

        $stmt->execute();
    }

    public function removeRequest($requestor, $dest = self::REQUEST_BECOME_PI)
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

    public function removeRequests($dest = self::REQUEST_BECOME_PI)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE request_for=:request_for"
        );
        $stmt->bindParam(":request_for", $dest);

        $stmt->execute();
    }

    public function getRequest($user, $dest)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for"
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

    public function requestExists($requestor, $dest = self::REQUEST_BECOME_PI)
    {
        try {
            $this->getRequest($requestor, $dest);
            return true;
            // FIXME use a specific exception
        } catch (\Exception) {
            return false;
        }
    }

    public function getAllRequests()
    {
        $stmt = $this->conn->prepare("SELECT * FROM " . self::TABLE_REQS);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRequests($dest = self::REQUEST_BECOME_PI)
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

    public function addNotice($title, $date, $content, $operator)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_NOTICES . " (date, title, message) VALUES (:date, :title, :message)"
        );
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $content);

        $stmt->execute();

        $this->addLog(
            $operator->uid,
            $_SERVER['REMOTE_ADDR'],
            "added_cluster_notice",
            $operator
        );
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

    public function editPage($id, $content, $operator)
    {
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_PAGES . " SET content=:content WHERE page=:id"
        );
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":content", $content);

        $stmt->execute();

        $this->addLog(
            $operator->uid,
            $_SERVER['REMOTE_ADDR'],
            "edited_page",
            $operator
        );
    }

    // audit log table methods
    public function addLog($operator, $operator_ip, $action_type, $recipient)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_AUDIT_LOG . " (operator, operator_ip, action_type, recipient)
            VALUE (:operator, :operator_ip, :action_type, :recipient)"
        );
        $stmt->bindParam(":operator", $operator);
        $stmt->bindParam(":operator_ip", $operator_ip);
        $stmt->bindParam(":action_type", $action_type);
        $stmt->bindParam(":recipient", $recipient);

        $stmt->execute();
    }

    public function addAccountDeletionRequest($uid)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_ACCOUNT_DELETION_REQUESTS . " (uid) VALUE (:uid)"
        );
        $stmt->bindParam(":uid", $uid);

        $stmt->execute();
    }

    public function accDeletionRequestExists($uid)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_ACCOUNT_DELETION_REQUESTS . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $uid);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function deleteAccountDeletionRequest($uid)
    {
        if (!$this->accDeletionRequestExists($uid)) {
            return;
        }
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_ACCOUNT_DELETION_REQUESTS . " WHERE uid=:uid"
        );
        $stmt->bindParam(":uid", $uid);
        $stmt->execute();
    }

    public function getSiteVar($name)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_SITEVARS . " WHERE name=:name"
        );
        $stmt->bindParam(":name", $name);

        $stmt->execute();

        return $stmt->fetchAll()[0]['value'];
    }

    public function updateSiteVar($name, $value)
    {
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_SITEVARS . " SET value=:value WHERE name=:name"
        );
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":value", $value);

        $stmt->execute();
    }

    public function getRole($uid, $group)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
        );
        $stmt->bindParam(":uid", $uid);
        $stmt->bindParam(":group", $group);

        $stmt->execute();

        return $stmt->fetchAll()[0]['role'];
    }

    public function hasPerm($role, $perm)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:role"
        );
        $stmt->bindParam(":role", $role);

        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        $perms = explode(",", $row['perms']);
        return in_array($perm, $perms);
    }

    public function getPriority($role)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:role"
        );
        $stmt->bindParam(":role", $role);

        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        return $row['priority'];
    }

    public function roleAvailableInGroup($uid, $group, $role)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
        );
        $stmt->bindParam(":uid", $uid);
        $stmt->bindParam(":group", $group);

        $stmt->execute();
        $row = $stmt->fetchAll()[0];

        $group_slug = $row['group'];

        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );

        $stmt->bindParam(":slug", $group_slug);
        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        $roles = explode(",", $row['roles']);

        return in_array($role, $roles);
    }
}
