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

    private function search($table, $filters)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM $table WHERE " .
                implode(" and ", array_map(fn($x) => "$x=:$x", array_keys($filters)))
        );
        foreach ($filters as $key => $val) {
            $stmt->bindParam(":$key", $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function delete($table, $filters)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM $table WHERE " .
                implode(" and ", array_map(fn($x) => "$x=:$x", array_keys($filters)))
        );
        foreach ($filters as $key => $val) {
            $stmt->bindParam(":$key", $val);
        }
        $stmt->execute();
    }

    private function insert($table, $data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO $table " .
                "(" . implode(", ", array_keys($data)) . ") " .
                "VALUES " .
                "(" . implode(", ", array_map(fn($x) => ":$x", array_keys($data))) . ")"
        );
        foreach ($data as $key => $val) {
            $stmt->bindParam(":$key", $val);
        }
        $stmt->execute();
    }

    private function update($table, $filters, $data)
    {
        // "UPDATE " . self::TABLE_NOTICES . " SET date=:date, title=:title, message=:message WHERE id=:id"
        $stmt = $this->conn->prepare(
            "UPDATE $table SET" .
                implode(", ", array_map(fn($x) => "$x=:$x", array_keys($filters))) .
                "WHERE " .
                implode(" and ", array_map(fn($x) => "$x=:$x", array_keys($filters)))
        );
        foreach ($filters as $key => $val) {
            $stmt->bindParam(":$key", $val);
        }
        foreach ($data as $key => $val) {
            $stmt->bindParam(":$key", $val);
        }
        $stmt->execute();
    }

    //
    // requests table methods
    //
    public function addRequest($requestor, $dest = self::REQUEST_BECOME_PI)
    {
        if ($this->requestExists($requestor, $dest)) {
            return;
        }
        $this->insert(self::TABLE_REQS, ["uid" => $requestor, "request_for" => $dest]);
    }

    public function removeRequest($requestor, $dest = self::REQUEST_BECOME_PI)
    {
        if (!$this->requestExists($requestor, $dest)) {
            return;
        }
        $this->delete(self::TABLE_REQS, ["uid" => $uid, "request_for" => $dest]);
    }

    public function removeRequests($dest = self::REQUEST_BECOME_PI)
    {
        $this->delete(self::TABLE_REQS, ["request_for" => $dest]);
    }

    public function requestExists($requestor, $dest = self::REQUEST_BECOME_PI)
    {
        $results = $this->search(self::TABLE_REQS, ["request_for" => $dest]);
        return count($results) > 0;
    }

    public function getRequests($dest = self::REQUEST_BECOME_PI)
    {
        return $this->search(self::TABLE_REQS, ["request_for" => $dest]);
    }

    public function getRequestsByUser($uid)
    {
        return $this->search(self::TABLE_REQS, ["uid" => $uid]);
    }

    public function deleteRequestsByUser($user)
    {
        $this->delete(self::TABLE_REQS, ["uid" => $uid]);
    }

    public function addNotice($title, $date, $content, $operator)
    {
        $this->insert(self::TABLE_NOTICES, ["date" => $date, "title" => $title, "message" => $content]);

        $operator = $operator->getUID();

        $this->addLog(
            $operator,
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
        $this->delete(self::TABLE_NOTICES, ["id" => $id]);
    }

    public function getNotice($id)
    {
        return $this->search(self::TABLE_NOTICES, ["id" => $id]);
    }

    public function getNotices()
    {
        return $this->search(self::TABLE_NOTICES, []);
    }

    public function getPages()
    {
        return $this->search(self::TABLE_PAGES, []);
    }

    public function getPage($id)
    {
        return $this->search(self::TABLE_PAGES, ["page" => $id]);
    }

    public function editPage($id, $content, $operator)
    {
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_PAGES . " SET content=:content WHERE page=:id"
        );
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":content", $content);

        $stmt->execute();

        $operator = $operator->getUID();

        $this->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "edited_page",
            $operator
        );
    }

    // audit log table methods
    public function addLog($operator, $operator_ip, $action_type, $recipient)
    {
        $this->insert(
            self::TABLE_AUDIT_LOG,
            [
                "operator" => $operator,
                "operator_ip" => $operator_ip,
                "action_type" => $action_type,
                "recipient" => $recipient
            ]
        );
    }

    public function addAccountDeletionRequest($uid)
    {
        $this->insert(self::TABLE_ACCOUNT_DELETION_REQUESTS, ["uid" => $uid]);
    }

    public function accDeletionRequestExists($uid)
    {
        $results = $this->search(self::TABLE_ACCOUNT_DELETION_REQUESTS, ["uid" => $uid]);
        return count($results) > 0;
    }

    public function deleteAccountDeletionRequest($uid)
    {
        if (!$this->accDeletionRequestExists($uid)) {
            return;
        }
        $this->delete(self::TABLE_ACCOUNT_DELETION_REQUESTS, ["uid" => $uid]);
    }

    public function getSiteVar($name)
    {
        return $this->search(self::TABLE_SITEVARS, ["name" => $name]);
    }

    public function updateSiteVar($name, $value)
    {
        $this->update(self::TABLE_SITEVARS, ["value" => $value, "name" => $name]);
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
