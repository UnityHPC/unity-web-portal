<?php

// REQUIRES config.php
// REQUIRES unity-ldap.php
// REQUIRES slurm.php

/**
 * Class that represents a single PI group in the Unity Cluster.
 */
class unityAccount
{
    const PI_PREFIX = "pi_";

    private $pi_uid;

    // Services
    private $service_stack;

    /**
     * Constructor for the object
     *
     * @param string $pi_uid PI UID in the format <PI_PREFIX><OWNER_UID>
     * @param unityLDAP $unityLDAP LDAP Connection
     * @param unitySQL $unitySQL SQL Connection
     */
    public function __construct($pi_uid, $service_stack)
    {
        $this->pi_uid = $pi_uid;

        if (is_null($service_stack->ldap())) {
            throw new Exception("LDAP is required for the unityUser class");
        }

        if (is_null($service_stack->sql())) {
            throw new Exception("SQL is required for the unityUser class");
        }

        $this->service_stack = $service_stack;
    }

    public function equals($other_group) {
        if (!is_a($other_group, self::class)) {
            throw new Exception("Unable to check equality because the parameter is not a " . self::class . " object");
        }

        return $this->getPIUID() == $other_group->getPIUID();
    }

    /**
     * Returns this group's PI UID
     *
     * @return string PI UID of the group
     */
    public function getPIUID() {
        return $this->pi_uid;
    }

    /**
     * Checks if the current PI is an approved and existent group
     *
     * @return bool true if yes, false if no
     */
    public function exists()
    {
        return $this->getLDAPPiGroup()->exists();
    }

    public function createGroup()
    {
        // make this user a PI
        $owner = $this->getOwner();

        // (1) Create LDAP PI group
        $ldapPiGroupEntry = $this->getLDAPPiGroup();

        if (!$ldapPiGroupEntry->exists()) {
            $nextGID = $this->service_stack->ldap()->getNextPiGID();

            $ldapPiGroupEntry->setAttribute("objectclass", unityLDAP::POSIX_GROUP_CLASS);
            $ldapPiGroupEntry->setAttribute("gidnumber", strval($nextGID));
            $ldapPiGroupEntry->setAttribute("memberuid", array($owner->getUID()));  // add current user as the first memberuid

            if (!$ldapPiGroupEntry->write()) {
                $this->logger->logCritical("Failed to create LDAP PI group for " . $this->getPIUID());
                $this->logger->killPortal();
                throw new Exception("Failed to create POSIX group for " . $owner->getUID());  // this shouldn't execute
            }
        }
    }

    public function removeGroup() {
        $this->service_stack->sql()->removeRequests($this->pi_uid);  // remove any lasting requests

        $users = $this->getGroupMembers();
        foreach ($users as $user) {
            $this->removeUserFromGroup($user);
        }

        // remove admin
        $this->removeUserFromGroup($this->getOwner());

        $ldapPiGroupEntry = $this->getLDAPPiGroup();
        if ($ldapPiGroupEntry->exists()) {
            if (!$ldapPiGroupEntry->delete()) {
                $this->logger->logCritical("Failed to delete LDAP PI group for " . $this->getPIUID());
                $this->logger->killPortal();
                throw new Exception("Unable to delete PI ldap group");
            }
        }
    }

    public function getOwner() {
        return new unityUser(self::getUIDfromPIUID($this->pi_uid), $this->service_stack);
    }

    public function getLDAPPiGroup()
    {
        $group_entries = $this->service_stack->ldap()->pi_groupOU->getChildren(true, "(" . unityLDAP::RDN . "=" . $this->pi_uid . ")");

        if (count($group_entries) > 0) {
            return $group_entries[0];
        } else {
            return new ldapEntry($this->service_stack->ldap()->getConn(), unityLDAP::RDN . "=$this->pi_uid," . unityLDAP::PI_GROUPS);
        }
    }

    public function addUserToGroup($new_user)
    {
        // Add to LDAP Group
        $pi_group = $this->getLDAPPiGroup();
        $pi_group->appendAttribute("memberuid", $new_user->getUID());
    }

    public function removeUserFromGroup($old_user)
    {
        // Remove from LDAP Group
        $pi_group = $this->getLDAPPiGroup();
        $pi_group->removeAttributeEntryByValue("memberuid", $old_user->getUID());
    }

    public function getGroupMembers()
    {
        $pi_group = $this->getLDAPPiGroup();
        $members = $pi_group->getAttribute("memberuid");

        $out = array();
        $owner_uid = $this->getOwner()->getUID();
        foreach ($members as $member) {
            if ($member != $owner_uid) {
                array_push($out, new unityUser($member, $this->service_stack));
            }
        }

        return $out;
    }

    public function getGroupMemberUIDs() {
        $pi_group = $this->getLDAPPiGroup();
        $members = $pi_group->getAttribute("memberuid");

        return $members;
    }

    public function addRequest($uid)
    {
        $this->service_stack->sql()->addRequest($uid, $this->pi_uid);
    }

    public function removeRequest($uid)
    {
        $this->service_stack->sql()->removeRequest($uid, $this->pi_uid);
    }

    public function getRequests()
    {
        $requests = $this->service_stack->sql()->getRequests($this->pi_uid);

        $out = array();
        foreach ($requests as $request) {
            array_push($out, new unityUser($request["uid"], $this->service_stack));
        }

        return $out;
    }

    public function requestExists($user) {
        foreach ($this->getRequests() as $requester) {
            if ($requester->getUID() == $user->getUID()) {
                return true;
            }
        }

        return false;
    }

    public function userExists($user) {
        return in_array($user->getUID(), $this->getGroupMemberUIDs());
    }

    public static function getPIFromPIGroup($pi_netid)
    {
        if (substr($pi_netid, 0, strlen(self::PI_PREFIX)) == self::PI_PREFIX) {
            return substr($pi_netid, strlen(self::PI_PREFIX));
        } else {
            throw new Exception("PI netid doesn't have the correct prefix.");
        }
    }

    public static function getPIUIDfromUID($uid)
    {
        return self::PI_PREFIX . $uid;
    }

    public static function getUIDfromPIUID($pi_uid)
    {
        if (substr($pi_uid, 0, strlen(self::PI_PREFIX)) == self::PI_PREFIX) {
            return substr($pi_uid, strlen(self::PI_PREFIX));
        } else {
            throw new Exception("PI netid doesn't have the correct prefix.");
        }
    }
}