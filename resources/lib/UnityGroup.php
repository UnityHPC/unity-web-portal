<?php

namespace UnityWebPortal\lib;

use Exception;

/**
 * Class that represents a single PI group in the Unity Cluster.
 */
class UnityGroup
{
    public const PI_PREFIX = "pi_";

    private $group_uid; // change to group_uid;

    // Services
    private $LDAP;
    private $SQL;
    private $MAILER;
    private $WEBHOOK;
    private $REDIS;

    /**
     * Constructor for the object
     *
     * @param string $group_uid Group UID in the format <GROUP_PREFIX><OWNER_UID>
     * @param LDAP $LDAP LDAP Connection
     * @param SQL $SQL SQL Connection
     */
    public function __construct($group_uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK)
    {
        $this->group_uid = $group_uid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->REDIS = $REDIS;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function equals($other_group)
    {
        if (!is_a($other_group, self::class)) {
            throw new Exception("Unable to check equality because the parameter is not a " . self::class . " object");
        }

        return $this->getGroupUID() == $other_group->getGroupUID();
    }

    /**
     * Returns this group's Group UID
     *
     * @return string Group UID of the group
     */
    public function getGroupUID() // change this to groupUID
    {
        return $this->group_uid;
    }

    /**
     * Checks if the current PI is an approved and existent group
     *
     * @return bool true if yes, false if no
     */
    public function exists()
    {
        return $this->getLDAPUnityGroup()->exists();
    }

    //
    // Portal-facing methods, these are the methods called by scripts in webroot
    //

    public function requestGroup($send_mail_to_admins, $send_mail = true)
    {
        // check for edge cases...
        if ($this->exists()) {
            return;
        }

        // check if account deletion request already exists
        if ($this->SQL->accDeletionRequestExists($this->getOwner()->getUID())) {
            return;
        }

        $this->SQL->addRequest($this->getOwner()->getUID());

        if ($send_mail) {
            // send email to requestor
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_request"
            );

            $this->WEBHOOK->sendWebhook(
                "group_request_admin",
                array(
                    "user" => $this->getOwner()->getUID(),
                    "org" => $this->getOwner()->getOrg(),
                    "name" => $this->getOwner()->getFullname(),
                    "email" => $this->getOwner()->getMail()
                )
            );

            if ($send_mail_to_admins) {
                $this->MAILER->sendMail(
                    "admin",
                    "group_request_admin",
                    array(
                        "user" => $this->getOwner()->getUID(),
                        "org" => $this->getOwner()->getOrg(),
                        "name" => $this->getOwner()->getFullname(),
                        "email" => $this->getOwner()->getMail()
                    )
                );
            }

            $this->MAILER->sendMail(
                "pi_approve",
                "group_request_admin",
                array(
                    "user" => $this->getOwner()->getUID(),
                    "org" => $this->getOwner()->getOrg(),
                    "name" => $this->getOwner()->getFullname(),
                    "email" => $this->getOwner()->getMail()
                )
            );
        }
    }

    /**
     * This method will create the group (this is what is executed when an admin approved the group)
     */
    public function approveGroup($operator = null, $send_mail = true)
    {
        // check for edge cases...
        if ($this->exists()) {
            return;
        }

        // check if owner exists
        if (!$this->getOwner()->exists()) {
            $this->getOwner()->init();
        }

        // initialize ldap objects, if this fails the script will crash, but nothing will persistently break
        $this->init();

        // remove the request from the sql table
        // this will silently fail if the request doesn't exist
        $this->SQL->removeRequest($this->getOwner()->getUID());

        $operator = is_null($operator) ? $this->getOwner()->getUID() : $operator->getUID();

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "approved_group",
            $this->getOwner()->getUID()
        );

        // send email to the newly approved PI
        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_created"
            );
        }
    }

    /**
     * This method is executed when an admin denys the PI group request
     */
    public function denyGroup($operator = null, $send_mail = true)
    {
        // remove request - this will fail silently if the request doesn't exist
        $this->SQL->removeRequest($this->getOwner()->getUID());

        if ($this->exists()) {
            return;
        }

        $operator = is_null($operator) ? $this->getOwner()->getUID() : $operator->getUID();

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "denied_group",
            $this->getOwner()->getUID()
        );

        // send email to the requestor
        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_denied"
            );
        }
    }

    /**
     * This method will delete the group, either by admin action or PI action
     */
    public function removeGroup($send_mail = true)
    {
        // remove any pending requests
        // this will silently fail if the request doesn't exist (which is what we want)
        $this->SQL->removeRequests($this->group_uid);

        // we don't need to do anything extra if the group is already deleted
        if (!$this->exists()) {
            return;
        }

        // first, we must record the users in the group currently
        $users = $this->getGroupMembers();

        // now we delete the ldap entry
        $ldapGroupEntry = $this->getLDAPUnityGroup();
        if ($ldapGroupEntry->exists()) {
            if (!$ldapGroupEntry->delete()) {
                throw new Exception("Unable to delete PI ldap group");
            }

            $this->REDIS->removeCacheArray("sorted_groups", "", $this->getGroupUID());
            foreach ($users as $user) {
                $this->REDIS->removeCacheArray($user->getUID(), "groups", $this->getGroupUID());
            }
        }

        // send email to every user of the now deleted PI group
        if ($send_mail) {
            foreach ($users as $user) {
                $this->MAILER->sendMail(
                    $user->getMail(),
                    "group_disband",
                    array("group_name" => $this->group_uid)
                );
            }
        }
    }

    /**
     * This method is executed when a user is approved to join the group (either by admin or the group owner)
     */
    public function approveUser($new_user, $send_mail = true)
    {
        // check if user exists
        if (!$new_user->exists()) {
            $new_user->init();
        }

        // add user to the LDAP object
        $this->addUserToGroup($new_user);

        // remove request, this will fail silently if the request doesn't exist
        $this->removeRequest($new_user->getUID());

        // send email to the requestor
        if ($send_mail) {
            // send email to the user
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_added",
                array("group" => $this->group_uid)
            );
            // send email to the PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_added_owner",
                array(
                    "group" => $this->group_uid,
                    "user" => $new_user->getUID(),
                    "name" => $new_user->getFullName(),
                    "email" => $new_user->getMail(),
                    "org" => $new_user->getOrg()
                    )
            );
        }
    }

    public function denyUser($new_user, $send_mail = true)
    {
        if (!$this->requestExists($new_user)) {
            return;
        }

        // remove request, this will fail silently if the request doesn't exist
        $this->removeRequest($new_user->getUID());

        if ($send_mail) {
            // send email to the user
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_denied",
                array("group" => $this->group_uid)
            );

            // send email to the PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_denied_owner",
                array(
                    "group" => $this->group_uid,
                    "user" => $new_user->getUID(),
                    "name" => $new_user->getFullName(),
                    "email" => $new_user->getMail(),
                    "org" => $new_user->getOrg()
                    )
            );
        }
    }

    public function removeUser($new_user, $send_mail = true)
    {
        if (!$this->userExists($new_user)) {
            return;
        }

        if ($new_user->getUID() == $this->getOwner()->getUID()) {
            throw new Exception("Cannot delete group owner from group. Disband group instead");
        }

        // remove request, this will fail silently if the request doesn't exist
        $this->removeUserFromGroup($new_user);

        if ($send_mail) {
            // send email to the user
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_removed",
                array("group" => $this->group_uid)
            );

            // send email to the PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_removed_owner",
                array(
                    "group" => $this->group_uid,
                    "user" => $new_user->getUID(),
                    "name" => $new_user->getFullName(),
                    "email" => $new_user->getMail(),
                    "org" => $new_user->getOrg()
                    )
            );
        }
    }

    public function newUserRequest($new_user, $send_mail = true)
    {
        if ($this->userExists($new_user)) {
            return;
        }

        if ($this->requestExists($new_user)) {
            return;
        }

        // check if account deletion request already exists
        if ($this->SQL->accDeletionRequestExists($new_user->getUID())) {
            return;
        }

        $this->addRequest($new_user->getUID());

        if ($send_mail) {
            // send email to user
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_request",
                array("group" => $this->group_uid)
            );

            // send email to PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_request_owner",
                array(
                    "group" => $this->group_uid,
                    "user" => $new_user->getUID(),
                    "name" => $new_user->getFullName(),
                    "email" => $new_user->getMail(),
                    "org" => $new_user->getOrg()
                    )
            );
        }
    }

    public function getRequests()
    {
        $requests = $this->SQL->getRequests($this->group_uid);

        $out = array();
        foreach ($requests as $request) {
            $user = new UnityUser(
                $request["uid"],
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->REDIS,
                $this->WEBHOOK
            );
            array_push($out, [$user, $request["timestamp"]]);
        }

        return $out;
    }

    public function getGroupMembers($ignorecache = false)
    {
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->getGroupUID(), "members");
            if (!is_null($cached_val)) {
                $members = $cached_val;
            }
        }

        $updatecache = false;
        if (!isset($members)) {
            $group = $this->getLDAPUnityGroup();
            $members = $group->getAttribute("memberuid");
            $updatecache = true;
        }

        $out = array();
        $cache_arr = array();
        $owner_uid = $this->getOwner()->getUID();
        foreach ($members as $member) {
                $user_obj = new UnityUser(
                    $member,
                    $this->LDAP,
                    $this->SQL,
                    $this->MAILER,
                    $this->REDIS,
                    $this->WEBHOOK
                );
                array_push($out, $user_obj);
                array_push($cache_arr, $user_obj->getUID());
        }

        if (!$ignorecache && $updatecache) {
            sort($cache_arr);
            $this->REDIS->setCache($this->getGroupUID(), "members", $cache_arr);
        }

        return $out;
    }

    public function getGroupMemberUIDs()
    {
        $group = $this->getLDAPUnityGroup();
        $members = $group->getAttribute("memberuid");

        return $members;
    }

    public function requestExists($user)
    {
        $requesters = $this->getRequests();
        if (count($requesters) > 0) {
            foreach ($requesters as $requester) {
                if ($requester[0]->getUID() == $user->getUID()) {
                    return true;
                }
            }
        }

        return false;
    }

    //
    // Private functions called by functions above
    //

    private function init()
    {
        // make this user a PI
        $owner = $this->getOwner();

        // (1) Create LDAP PI group
        $ldapGroupEntry = $this->getLDAPUnityGroup();

        if (!$ldapGroupEntry->exists()) {
            $nextGID = $this->LDAP->getNextGIDNumber($this->SQL);

            $ldapGroupEntry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $ldapGroupEntry->setAttribute("gidnumber", strval($nextGID));
            $ldapGroupEntry->setAttribute("memberuid", array($owner->getUID()));

            if (!$ldapGroupEntry->write()) {
                throw new Exception("Failed to create POSIX group for " . $owner->getUID());  // this shouldn't execute
            }
        }

        $this->REDIS->appendCacheArray("sorted_groups", "", $this->getGroupUID());

        // TODO if we ever make this project based, we need to update the cache here with the memberuid
    }

    private function addUserToGroup($new_user)
    {
        // Add to LDAP Group
        $group = $this->getLDAPUnityGroup();
        $group->appendAttribute("memberuid", $new_user->getUID());

        if (!$group->write()) {
            throw new Exception("Unable to write PI group");
        }

        $this->REDIS->appendCacheArray($this->getGroupUID(), "members", $new_user->getUID());
        $this->REDIS->appendCacheArray($new_user->getUID(), "groups", $this->getGroupUID());
    }

    private function removeUserFromGroup($old_user)
    {
        // Remove from LDAP Group
        $group = $this->getLDAPUnityGroup();
        $group->removeAttributeEntryByValue("memberuid", $old_user->getUID());

        if (!$group->write()) {
            throw new Exception("Unable to write PI group");
        }

        $this->REDIS->removeCacheArray($this->getGroupUID(), "members", $old_user->getUID());
        $this->REDIS->removeCacheArray($old_user->getUID(), "groups", $this->getGroupUID());
    }

    public function userExists($user)
    {
        return in_array($user->getUID(), $this->getGroupMemberUIDs());
    }

    private function addRequest($uid)
    {
        $this->SQL->addRequest($uid, $this->group_uid);
    }

    private function removeRequest($uid)
    {
        $this->SQL->removeRequest($uid, $this->group_uid);
    }

    //
    // Public helper functions
    //

    public function getOwner()
    {
        return new UnityUser(
            self::getUIDfromGroupUID($this->group_uid),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->REDIS,
            $this->WEBHOOK
        );
    }

    public function getLDAPUnityGroup()
    {
        return $this->LDAP->getUnityGroupEntry($this->group_uid);
    }

    public static function getGroupUIDfromUID($uid)
    {
        return self::PI_PREFIX . $uid;
    }

    public static function getUIDfromGroupUID($group_uid)
    {
        if (substr($group_uid, 0, strlen(self::PI_PREFIX)) == self::PI_PREFIX) {
            return substr($group_uid, strlen(self::PI_PREFIX));
        } else {
            throw new Exception("PI netid doesn't have the correct prefix.");
        }
    }

    public function getGroupColor()
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $group_type = $this->LDAP->getGroupType($gid);
        $group_details = $this->SQL->getGroupTypeDetails($group_type);
        return $group_details["color"];
    }

    public function getGroupName()
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $group_type = $this->LDAP->getGroupType($gid);
        $group_details = $this->SQL->getGroupTypeDetails($group_type);
        return $group_details["name"];
    }

    public function getAvailableRoles()
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $group_type = $this->LDAP->getGroupType($gid);
        $group_details = $this->SQL->getGroupTypeDetails($group_type);
        $av_roles = $group_details["av_roles"];

        $out = array();
        foreach ($av_roles as $role) {
            $role_obj = array();
            $role_obj["slug"] = $role;
            $role_obj["display_name"] = $this->SQL->getRoleName($role);
            array_push($out, $role_obj);
        }

        return $out;
    }

    public function getUsersWithRole($role)
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $users = $this->SQL->getUsersWithRoles($role, $gid);

        $out = array();
        foreach ($users as $user) {
            $user_obj = new UnityUser(
                $user,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->REDIS,
                $this->WEBHOOK
            );
            array_push($out, $user_obj);
        }

        return $out;
    }

    public function getUsersWithoutRole()
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $current_users_uids = $this->getGroupMemberUIDs();
        $users = $this->SQL->getUsersWithoutRoles($gid, $current_users_uids);

        $out = array();
        foreach ($users as $user) {
            $user_obj = new UnityUser(
                $user,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->REDIS,
                $this->WEBHOOK
            );
            array_push($out, $user_obj);
        }

        return $out;
    }

    public function assignRole($user, $role)
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $this->SQL->assignRole($role, $user->getUID(), $gid);
    }

    public function revokeRole($user, $role)
    {
        $gid = $this->getLDAPUnityGroup()->getAttribute("cn")[0];
        $this->SQL->revokeRole($role, $user, $gid);
    }
}
