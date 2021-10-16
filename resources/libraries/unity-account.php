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
     * @param slurm $sacctmgr Slurm Connection
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

        if (is_null($service_stack->sacctmgr())) {
            throw new Exception("sacctmgr is required for the unityUser class");
        }

        if (is_null($service_stack->unityfs())) {
            throw new Exception("unityfs is required for the unityUser class");
        }

        $this->service_stack = $service_stack;
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
        return $this->service_stack->sacctmgr()->accountExists($this->pi_uid);
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
                throw new Exception("Failed to create POSIX group for " . $owner->getUID());
            }
        }

        // (2) Create slurm account
        $this->createSlurmAccount();
        $this->addAssociation(self::getUIDfromPIUID($this->pi_uid));  // add owner user
    }

    public function removeGroup() {
        $ldapPiGroupEntry = $this->getLDAPPiGroup();
        if (!$ldapPiGroupEntry->delete()) {
            throw new Exception("Unable to delete PI ldap group");
        }

        $this->service_stack->sql()->removeRequests($this->pi_uid);  // remove any lasting requests

        $this->removeSlurmAccount();
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
        // Create Association
        $this->addAssociation($new_user->getUID());

        // Add to LDAP Group
        $pi_group = $this->getLDAPPiGroup();
        $pi_group->appendAttribute("memberuid", $new_user->getUID());
        if (!$pi_group->write()) {
            // failed to write
            $this->removeAssociation($new_user->getUID());
        }
    }

    public function removeUserFromGroup($old_user)
    {
        // Remove Association
        $this->removeAssociation($old_user->getUID());

        // Remove from LDAP Group
        $pi_group = $this->getLDAPPiGroup();
        $pi_group->removeAttributeEntryByValue("memberuid", $old_user->getUID());
        if (!$pi_group->write()) {
            // failed to write
            $this->addAssociation($old_user->getUID());
        }
    }

    //
    //  Slurm Related Functions
    //

    /**
     * Creates a group based off this user (this user is a PI)
     */
    private function createSlurmAccount()
    {
        $this->service_stack->sacctmgr()->addAccount($this->pi_uid);
    }

    private function removeSlurmAccount()
    {
        $this->service_stack->sacctmgr()->deleteAccount($this->pi_uid);
    }

    private function addAssociation($uid)
    {
        if (!$this->service_stack->sacctmgr()->accountExists($this->pi_uid)) {
            throw new Exception("Unable to create an association to a nonexist account $this->pi_uid");
        }

        // Add Slurm User
        $this->service_stack->sacctmgr()->addUser($uid, $this->pi_uid);
    }

    /**
     * Undocumented function
     *
     * @param [type] $netid
     * @return void
     */
    private function removeAssociation($uid)
    {
        if (!$this->service_stack->sacctmgr()->userExists($uid, $this->pi_uid)) {
            throw new Exception("Unable to remove association because an association doesn't exist");
        }

        $this->service_stack->sacctmgr()->deleteUser($uid, $this->pi_uid);
    }


    public function getAssociations()
    {
        return $this->service_stack->sacctmgr()->getUsersFromAccount($this->pi_uid);
    }

    public function getGroupMembers()
    {
        $members = $this->getAssociations();

        $out = array();
        $owner_uid = $this->getOwner()->getUID();
        foreach ($members as $member) {
            if ($member != $owner_uid) {
                array_push($out, new unityUser($member, $this->service_stack));
            }
        }

        return $out;
    }

    public static function getPIFromPIGroup($pi_netid)
    {
        if (substr($pi_netid, 0, strlen(self::PI_PREFIX)) == self::PI_PREFIX) {
            return substr($pi_netid, strlen(self::PI_PREFIX));
        } else {
            throw new Exception("PI netid doesn't have the correct prefix.");
        }
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