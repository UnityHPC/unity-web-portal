<?php

namespace UnityWebPortal\lib;

use PDO;
use PDOException;

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

    private function execute($statement)
    {
        try {
            $statement->execute();
        } catch (PDOException $e) {
            ob_start();
            $statement->debugDumpParams();
            $sql_debug_dump = ob_get_clean();
            throw new PDOException($sql_debug_dump, 0, $e);
        }
    }

    private function search($table, $filters)
    {
        if (count($filters) > 0) {
            $stmt = $this->conn->prepare(
                "SELECT * FROM $table WHERE " .
                    implode(" and ", array_map(fn($x) => "$x=:$x", array_keys($filters)))
            );
            foreach ($filters as $key => $val) {
                $stmt->bindValue(":$key", $val);
            }
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM $table");
        }
        $this->execute($stmt);
        return $stmt->fetchAll();
    }

    private function delete($table, $filters)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM $table WHERE " .
                implode(" and ", array_map(fn($x) => "$x=:$x", array_keys($filters)))
        );
        foreach ($filters as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $this->execute($stmt);
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
            $stmt->bindValue(":$key", $val);
        }
        $this->execute($stmt);
    }

    private function update($table, $filters, $data)
    {
        $stmt = $this->conn->prepare(
            "UPDATE $table SET " .
                implode(", ", array_map(fn($x) => "$x=:$x", array_keys($filters))) . " " .
                "WHERE " .
                implode(" and ", array_map(fn($x) => "$x=:$x", array_keys($filters)))
        );
        foreach ($filters as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        foreach ($data as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $this->execute($stmt);
    }

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
        $this->delete(self::TABLE_REQS, ["uid" => $requestor, "request_for" => $dest]);
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
        $this->delete(self::TABLE_REQS, ["uid" => $user]);
    }

    public function addNotice($title, $date, $content, $operator)
    {
        $this->insert(
            self::TABLE_NOTICES,
            ["date" => $date, "title" => $title, "message" => $content]
        );
        $operator = $operator->getUID();
        $this->addLog($operator, $_SERVER['REMOTE_ADDR'], "added_cluster_notice", $operator);
    }

    public function editNotice($id, $title, $date, $content)
    {
        $this->update(
            self::TABLE_PAGES,
            ["id" => $id],
            ["date" => $date, "title" => $title, "message" => $message]
        );
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
        $this->update(self::TABLE_PAGES, ["page" => $id], ["content" => $content]);
        $operator = $operator->getUID();
        $this->addLog($operator, $_SERVER['REMOTE_ADDR'], "edited_page", $operator);
    }

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

    public function getSiteVar($name): string
    {
        $results = $this->search(self::TABLE_SITEVARS, ["name" => $name]);
        if (count($results) == 0) {
            throw new UnitySQLRecordNotFound($name);
        }
        assert(count($results) == 1);
        return $results[0]["value"];
    }

    public function updateSiteVar($name, $value)
    {
        $this->update(self::TABLE_SITEVARS, ["name" => $name], ["value" => $value]);
    }

    public function getRole($uid, $group)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
        );
        $stmt->bindValue(":uid", $uid);
        $stmt->bindValue(":group", $group);

        $stmt->execute();

        return $stmt->fetchAll()[0]['role'];
    }

    public function hasPerm($role, $perm)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:role"
        );
        $stmt->bindValue(":role", $role);

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
        $stmt->bindValue(":role", $role);

        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        return $row['priority'];
    }

    public function roleAvailableInGroup($uid, $group, $role)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
        );
        $stmt->bindValue(":uid", $uid);
        $stmt->bindValue(":group", $group);

        $stmt->execute();
        $row = $stmt->fetchAll()[0];

        $group_slug = $row['group'];

        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );

        $stmt->bindValue(":slug", $group_slug);
        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        $roles = explode(",", $row['roles']);

        return in_array($role, $roles);
    }
}
