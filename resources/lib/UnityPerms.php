<?php

namespace UnityWebPortal\lib;

class UnityPerms
{
    private $SQL;
    private $USER;

    public function __construct($SQL, $USER)
    {
        $this->SQL = $SQL;
        $this->USER = $USER;
    }

    public function checkApproveUser($uid, $group)
    {
        if (!$this->USER->isInGroup($uid, $group)) {
            return false;
        }

        $role = $this->SQL->getRole($uid, $group);

        if ($this->SQL->hasPerm($role, 'unity.admin') || $this->SQL->hasPerm($role, 'unity.admin_no_grant')) {
            return true;
        }

        if (!$this->SQL->hasPerm($role, 'unity.approve_user')) {
            return false;
        }

        return true;
    }

    public function checkDenyUser($uid, $group)
    {
        if (!$this->USER->isInGroup($uid, $group)) {
            return false;
        }

        $role = $this->SQL->getRole($uid, $group);

        if ($this->SQL->hasPerm($role, 'unity.admin') || $this->SQL->hasPerm($role, 'unity.admin_no_grant')) {
            return true;
        }

        if (!$this->SQL->hasPerm($role, 'unity.deny_user')) {
            return false;
        }

        return true;
    }

    public function checkGrantRole($uid, $group, $role)
    {
        if (!$this->USER->isInGroup($uid, $group)) {
            return false;
        }

        if (!$this->SQL->roleAvailableInGroup($uid, $group, $role)) {
            return false;
        }

        $user_role = $this->SQL->getRole($uid, $group);

        if ($this->SQL->hasPerm($user_role, 'unity.admin_no_grant') && $this->SQL->hasPerm($role, 'unity.admin')) {
            return false;
        }

        if ($this->SQL->hasPerm($user_role, 'unity.admin') || $this->SQL->hasPerm($user_role, 'unity.admin_no_grant')) {
            return true;
        }

        if (!$this->SQL->hasPerm($user_role, 'unity.grant_role')) {
            return false;
        }

        if ($this->SQL->getPriority($role) >= $this->SQL->getPriority($user_role)) {
            return false;
        }

        return true;
    }

    public function checkRevokeRole($uid, $group, $role)
    {
        if (!$this->USER->isInGroup($uid, $group)) {
            return false;
        }

        if (!$this->SQL->roleAvailableInGroup($uid, $group, $role)) {
            return false;
        }

        $user_role = $this->SQL->getRole($uid, $group);

        if ($this->SQL->hasPerm($user_role, 'unity.admin_no_grant') && $this->SQL->hasPerm($role, 'unity.admin')) {
            return false;
        }

        if ($this->SQL->hasPerm($user_role, 'unity.admin') || $this->SQL->hasPerm($user_role, 'unity.admin_no_grant')) {
            return true;
        }

        if (!$this->SQL->hasPerm($user_role, 'unity.revoke_role')) {
            return false;
        }

        if ($this->SQL->getPriority($role) >= $this->SQL->getPriority($user_role)) {
            return false;
        }

        return true;
    }
}
