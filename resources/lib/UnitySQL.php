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
    private const TABLE_AUDIT_LOG = "audit_log";
    private const TABLE_ACCOUNT_DELETION_REQUESTS = "account_deletion_requests";
    private const TABLE_SITEVARS = "sitevars";
    private const TABLE_GROUP_ROLES = "groupRoles";
    private const TABLE_GROUP_TYPES = "groupTypes";
    private const TABLE_GROUP_ROLE_ASSIGNMENTS = "groupRoleAssignments";
    private const TABLE_GROUP_REQUESTS = "groupRequests";
    private const TABLE_GROUP_JOIN_REQUESTS = "groupJoinRequests";


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

        $operator = $operator->getUID();

        $this->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "edited_page",
            $operator
        );
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
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group_uid"
        );
        $stmt->bindParam(":uid", $uid);
        $stmt->bindParam(":group_uid", $group);

        $stmt->execute();
        $roles = array();
        foreach ($stmt->fetchAll() as $row) {
            $roles[] = $row['role'];
        }

        foreach ($roles as $role) {
            return $role;
        }
    }

    public function hasPerm($role, $perm)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:role"
        );
        $stmt->bindParam(":role", $role);

        $stmt->execute();

        $perms = array();
        foreach ($stmt->fetchAll() as $row) {
            $perms[] = $row['perms'];
        }

        foreach ($perms as $p) {
            $perms = explode(",", $p);
            if (in_array($perm, $perms)) {
                return true;
            }
        }
        return false;
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

        $group_slug = substr($row['group'], 0, strpos($row['group'], "_"));

        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );

        $stmt->bindParam(":slug", $group_slug);
        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        $roles = explode(",", $row['av_roles']);

        return in_array($role, $roles);
    }

    public function getGroupRoleAssignments($uid, $group_uid)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:uid AND `group`=:group"
        );
        $stmt->bindParam(":uid", $uid);
        $stmt->bindParam(":group", $group_uid);

        $stmt->execute();

        $roles = array();
        foreach ($stmt->fetchAll() as $row) {
            $roles[] = $row['role'];
        }

        return $roles;
    }

    public function getDefaultRole($group_type)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );
        $stmt->bindParam(":slug", $group_type);

        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        return $row['def_role'];
    }

    public function getGroupTypeDetails($group_type)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );
        $stmt->bindParam(":slug", $group_type);

        $stmt->execute();

        $row = $stmt->fetchAll()[0];

        $out = array();
        $out['name'] = $row['name'];
        $out['color'] = $row['color'];

        $av_roles = explode(",", $row['av_roles']);
        $out['av_roles'] = $av_roles;

        return $out;
    }

    public function getRoleName($role)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:slug"
        );
        $stmt->bindParam(":slug", $role);

        $stmt->execute();

        $row = $stmt->fetchAll()[0];
        return $row['name'];
    }

    public function getPermissions($roles)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLES . " WHERE slug=:slug"
        );

        $perms = array();
        foreach ($roles as $role) {
            $stmt->bindParam(":slug", $role);

            $stmt->execute();

            $row = $stmt->fetchAll()[0];
            $perms = array_merge($perms, explode(",", $row['perms']));
        }

        return $perms;
    }

    public function getUsersWithRoles($role, $group_uid)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE `group`=:group AND role=:role"
        );

        $stmt->bindParam(":group", $group_uid);
        $stmt->bindParam(":role", $role);

        $stmt->execute();

        $users = array();
        foreach ($stmt->fetchAll() as $row) {
            $users[] = $row['user'];
        }

        return $users;
    }

    public function getUsersWithoutRoles($group_uid, $curr_users_uids)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE `group`=:group"
        );

        $stmt->bindParam(":group", $group_uid);

        $stmt->execute();

        $users = array();
        foreach ($stmt->fetchAll() as $row) {
            $users[] = $row['user'];
        }

        $users = array_diff($curr_users_uids, $users);

        return $users;
    }

    public function assignRole($role, $uid, $gid)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " (user, `group`, role) VALUES (:user, :group, :role)"
        );

        $stmt->bindParam(":user", $uid);
        $stmt->bindParam(":group", $gid);
        $stmt->bindParam(":role", $role);

        $stmt->execute();
    }

    public function revokeRole($role, $uid, $gid)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_GROUP_ROLE_ASSIGNMENTS . " WHERE user=:user AND `group`=:group AND role=:role"
        );

        $stmt->bindParam(":user", $uid);
        $stmt->bindParam(":group", $gid);
        $stmt->bindParam(":role", $role);

        $stmt->execute();
    }

    public function getGroupTypes()
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE can_request=1"
        );

        $stmt->execute();

        $types = array();
        foreach ($stmt->fetchAll() as $row) {
            $types[] = array(
                "slug" => $row['slug'],
                "name" => $row['name'],
                "time_limited" => $row['time_limited'],
                "prefix" => $row['prefix'],
                "isNameable" => $row['isNameable'],
            );
        }

        return $types;
    }

    public function addGroupRequest($requestor, $group_type, $group_name, $start_date, $end_date)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_GROUP_REQUESTS . " (requestor, group_type, group_name, start_date, end_date) 
            VALUES (:requestor, :group_type, :group_name, :start_date, :end_date)"
        );

        $stmt->bindParam(":requestor", $requestor);
        $stmt->bindParam(":group_type", $group_type);
        $stmt->bindParam(":group_name", $group_name);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);

        $stmt->execute();
    }

    public function getPendingGroupRequests($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_REQUESTS . " WHERE requestor=:requestor"
        );

        $stmt->bindParam(":requestor", $user);

        $stmt->execute();

        $requests = array();
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = array(
                "id" => $row['id'],
                "group_type" => $row['group_type'],
                "group_name" => $row['group_name'],
                "requested_on" => $row['requested_on']
            );
        }

        return $requests;
    }

    public function getGroupRequests()
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_REQUESTS
        );

        $stmt->execute();

        $requests = array();
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = array(
                "id" => $row['id'],
                "requestor" => $row['requestor'],
                "group_type" => $row['group_type'],
                "group_name" => $row['group_name'],
                "requested_on" => $row['requested_on']
            );
        }

        return $requests;
    }

    public function removeGroupRequest($user)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_GROUP_REQUESTS . " WHERE requestor=:requestor"
        );

        $stmt->bindParam(":requestor", $user);

        $stmt->execute();
    }

    public function getRequestor($group)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_REQUESTS . " WHERE group_name=:group_name"
        );

        $stmt->bindParam(":group_name", $group);

        $stmt->execute();

        $row = $stmt->fetch();

        return $row['requestor'];
    }

    public function groupRequestExists($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_REQUESTS . " WHERE requestor=:requestor"
        );

        $stmt->bindParam(":requestor", $user);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function addJoinRequest($requestor, $group_uid)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . self::TABLE_GROUP_JOIN_REQUESTS . " (requestor, group_name) VALUES (:requestor, :group_name)"
        );

        $stmt->bindParam(":requestor", $requestor);
        $stmt->bindParam(":group_name", $group_uid);

        $stmt->execute();
    }

    public function removeJoinRequest($requestor, $group_uid)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_GROUP_JOIN_REQUESTS . " WHERE requestor=:requestor AND group_name=:group_name"
        );

        $stmt->bindParam(":requestor", $requestor);
        $stmt->bindParam(":group_name", $group_uid);

        $stmt->execute();
    }

    public function removeJoinRequests($group_uid)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . self::TABLE_GROUP_JOIN_REQUESTS . " WHERE group_name=:group_name"
        );

        $stmt->bindParam(":group_name", $group_uid);

        $stmt->execute();
    }

    public function getJoinRequests($group_uid)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_JOIN_REQUESTS . " WHERE group_name=:group_name"
        );

        $stmt->bindParam(":group_name", $group_uid);

        $stmt->execute();

        $requests = array();
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = array(
                "requestor" => $row['requestor'],
                "requested_on" => $row['requested_on']
            );
        }

        return $requests;
    }

    public function getJoinRequestsByUser($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_JOIN_REQUESTS . " WHERE requestor=:requestor"
        );

        $stmt->bindParam(":requestor", $user);

        $stmt->execute();

        $requests = array();
        foreach ($stmt->fetchAll() as $row) {
            $requests[] = array(
                "group_name" => $row['group_name'],
                "requested_on" => $row['requested_on']
            );
        }

        return $requests;
    }

    public function assignSuperRole($user, $group_type, $group_uid)
    {
        // get the defSuperRole property from the group types table using the $group as slug. then assign the user that role.
        $stmt = $this->conn->prepare(
            "SELECT defSuperRole FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );

        $stmt->bindParam(":slug", $group_type);

        $stmt->execute();

        $row = $stmt->fetch();

        $this->assignRole($row['defSuperRole'], $user, $group_uid);
    }

    public function getGroupAdmins($group_uid, $users)
    {
        // first get the roles of all users. then check if they have the unity.admin permission. if they do, add them to the admins array.
        $admins = array();
        foreach ($users as $user) {
            $role = $this->getRole($user, $group_uid);
            if ($this->hasPerm($role, "unity.admin")) {
                $admins[] = $user;
            }
        }
        return $admins;
    }

    public function assignDefRole($user, $group_type, $group_uid)
    {
        // get the defRole property from the group types table using the $group as slug. then assign the user that role.
        $stmt = $this->conn->prepare(
            "SELECT def_role FROM " . self::TABLE_GROUP_TYPES . " WHERE slug=:slug"
        );

        $stmt->bindParam(":slug", $group_type);

        $stmt->execute();

        $row = $stmt->fetch();

        $this->assignRole($row['def_role'], $user, $group_uid);
    }

    public function PIRequestExists($user)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_REQUESTS . " WHERE requestor=:requestor AND group_type='pi'"
        );

        $stmt->bindParam(":requestor", $user);

        $stmt->execute();

        return count($stmt->fetchAll()) > 0;
    }

    public function getGroupType($prefix)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . self::TABLE_GROUP_TYPES . " WHERE prefix=:prefix"
        );

        $stmt->bindParam(":prefix", $prefix);

        $stmt->execute();

        $row = $stmt->fetch();

        return $row['slug'] ?? null;
    }
}
