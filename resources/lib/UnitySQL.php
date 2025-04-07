<?php

namespace UnityWebPortal\lib;

use Exception;
use PDO;

class UnitySQLRecordNotFoundException extends Exception {}
class UnitySQLRecordNotUniqueException extends Exception {}
class UnitySQLDuplicateRequestException extends Exception {}

class UnitySQL
{
    private const TABLE_REQS = "requests";
    private const TABLE_NOTICES = "notices";
    private const TABLE_PAGES = "pages";
    private const TABLE_AUDIT_LOG = "audit_log";
    private const TABLE_ACCOUNT_DELETION_REQUESTS = "account_deletion_requests";
    private const TABLE_SITEVARS = "sitevars";
    // private const TABLE_GROUP_ROLES = "groupRoles";
    // private const TABLE_GROUP_TYPES = "groupTypes";
    // private const TABLE_GROUP_ROLE_ASSIGNMENTS = "groupRoleAssignments";
    // private const TABLE_GROUP_REQUESTS = "groupRequests";
    // private const TABLE_GROUP_JOIN_REQUESTS = "groupJoinRequests";


    // FIXME "admin" is legacy, should be changed to something more intuitive
    // this requires messing with the production database
    private const REQUEST_PI_PROMOTION = "admin";

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
    public function addRequest($requestor, $request_for = self::REQUEST_PI_PROMOTION)
    {
        if ($this->requestExists($requestor, $request_for)) {
            throw new UnitySQLDuplicateRequestException(json_encode([
                "requestor" => $requestor,
                "request_for" => $request_for
            ]));
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_REQS . " (uid, request_for) VALUES (:uid, :request_for)"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $request_for);

        $stmt->execute();
    }

    public function removeRequest($requestor, $request_for = self::REQUEST_PI_PROMOTION)
    {
        if (!$this->requestExists($requestor, $request_for)) {
            throw new UnitySQLRecordNotFoundException(json_encode([
                "requestor" => $requestor,
                "request_for" => $request_for
            ]));
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $request_for);

        $stmt->execute();
    }

    public function removeRequests($request_for = self::REQUEST_PI_PROMOTION)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_REQS . " WHERE request_for=:request_for"
        );
        $stmt->bindParam(":request_for", $request_for);

        $stmt->execute();
    }

    public function requestExists($requestor, $request_for = self::REQUEST_PI_PROMOTION)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE uid=:uid and request_for=:request_for"
        );
        $stmt->bindParam(":uid", $requestor);
        $stmt->bindParam(":request_for", $request_for);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function getRequests($request_for = self::REQUEST_PI_PROMOTION)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_REQS . " WHERE request_for=:request_for"
        );
        $stmt->bindParam(":request_for", $request_for);

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
        $this->getNotice($id); // fail if notice does not exist
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
        $this->getNotice($id); // fail if notice does not exist
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

        $sql_results = $stmt->fetchAll();
        if (count($sql_results) == 0) {
            throw new UnitySQLRecordNotFoundException($id);
        }
        if (count($sql_results) > 1) {
            throw new UnitySQLRecordNotUniqueException($id);
        }
        return $sql_results[0];
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
        $sql_results = $stmt->fetchAll();
        if (count($sql_results) == 0) {
            throw new UnitySQLRecordNotFoundException($id);
        }
        if (count($sql_results) > 1) {
            throw new UnitySQLRecordNotUniqueException($id);
        }
        return $sql_results[0];
    }

    public function editPage($id, $content, $operator)
    {
        $this->getPage($id); // fail if page doesn't exist
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

    public function getSiteVar($name)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_SITEVARS . " WHERE name=:name"
        );
        $stmt->bindParam(":name", $name);

        $stmt->execute();
        $sql_results = $stmt->fetchAll();
        if (count($sql_results) == 0) {
            throw new UnitySQLRecordNotFoundException($id);
        }
        if (count($sql_results) > 1) {
            throw new UnitySQLRecordNotUniqueException($id);
        }
        return $sql_results[0]["value"];
    }

    public function updateSiteVar($name, $value)
    {
        $this->getSiteVar($name); // fail if notice does not exist
        $stmt = $this->conn->prepare(
            "UPDATE " . self::TABLE_SITEVARS . " SET value=:value WHERE name=:name"
        );
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":value", $value);

        $stmt->execute();
    }

    // public function getRole($uid, $group)
    // {
    //     $stmt = $this->conn->prepare(
    //         "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
    //     );
    //     $stmt->bindParam(":uid", $uid);
    //     $stmt->bindParam(":group", $group);

    //     $stmt->execute();

    //     return $stmt->fetchAll()[0]['role'];
    // }

    // public function hasPerm($role, $perm)
    // {
    //     $stmt = $this->conn->prepare(
    //         "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:role"
    //     );
    //     $stmt->bindParam(":role", $role);

    //     $stmt->execute();

    //     $row = $stmt->fetchAll()[0];
    //     $perms = explode(",", $row['perms']);
    //     return in_array($perm, $perms);
    // }

    // public function getPriority($role)
    // {
    //     $stmt = $this->conn->prepare(
    //         "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:role"
    //     );
    //     $stmt->bindParam(":role", $role);

    //     $stmt->execute();

    //     $row = $stmt->fetchAll()[0];
    //     return $row['priority'];
    // }

    // public function roleAvailableInGroup($uid, $group, $role)
    // {
    //     $stmt = $this->conn->prepare(
    //         "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
    //     );
    //     $stmt->bindParam(":uid", $uid);
    //     $stmt->bindParam(":group", $group);

    //     $stmt->execute();
    //     $row = $stmt->fetchAll()[0];

    //     $group_slug = $row['group'];

    //     $stmt = $this->conn->prepare(
    //         "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
    //     );

    //     $stmt->bindParam(":slug", $group_slug);
    //     $stmt->execute();

    //     $row = $stmt->fetchAll()[0];
    //     $roles = explode(",", $row['roles']);

    //     return in_array($role, $roles);
    // }
}
