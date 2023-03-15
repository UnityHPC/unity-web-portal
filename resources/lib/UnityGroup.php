<?php

namespace UnityWebPortal\lib;

use Exception;

/**
 * Class that represents a single PI group in the Unity Cluster.
 */
class UnityGroup
{
    public const PI_PREFIX = "pi_";

    private $pi_uid;

    // Services
    private $LDAP;
    private $SQL;
    private $MAILER;
    private $REDIS;

    /**
     * Constructor for the object
     *
     * @param string $pi_uid PI UID in the format <PI_PREFIX><OWNER_UID>
     * @param LDAP $LDAP LDAP Connection
     * @param SQL $SQL SQL Connection
     */
    public function __construct($pi_uid, $LDAP, $SQL, $MAILER, $REDIS)
    {
        $this->pi_uid = $pi_uid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->REDIS = $REDIS;
    }

    public function equals($other_group)
    {
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
    public function getPIUID()
    {
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

    //
    // Portal-facing methods, these are the methods called by scripts in webroot
    //

    public function requestGroup($send_mail = true)
    {
        // check for edge cases...
        if ($this->exists()) {
            return;
        }

        $this->SQL->addRequest($this->getOwner()->getUID());

        if ($send_mail) {
            // send email to requestor
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_request"
            );

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
    public function approveGroup($send_mail = true)
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
    public function denyGroup($send_mail = true)
    {
        // remove request - this will fail silently if the request doesn't exist
        $this->SQL->removeRequest($this->getOwner()->getUID());

        if ($this->exists()) {
            return;
        }

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
        $this->SQL->removeRequests($this->pi_uid);

        // we don't need to do anything extra if the group is already deleted
        if (!$this->exists()) {
            return;
        }

        // first, we must record the users in the group currently
        $users = $this->getGroupMembers();

        // now we delete the ldap entry
        $ldapPiGroupEntry = $this->getLDAPPiGroup();
        if ($ldapPiGroupEntry->exists()) {
            if (!$ldapPiGroupEntry->delete()) {
                throw new Exception("Unable to delete PI ldap group");
            }
        }

        // send email to every user of the now deleted PI group
        if ($send_mail) {
            foreach ($users as $user) {
                $this->MAILER->sendMail(
                    $user->getMail(),
                    "group_disband",
                    array("group_name" => $this->pi_uid)
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
                array("group" => $this->pi_uid)
            );
            // send email to the PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_added_owner",
                array(
                    "group" => $this->pi_uid,
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
                array("group" => $this->pi_uid)
            );

            // send email to the PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_denied_owner",
                array(
                    "group" => $this->pi_uid,
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

        // remove request, this will fail silently if the request doesn't exist
        $this->removeUserFromGroup($new_user);

        if ($send_mail) {
            // send email to the user
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_removed",
                array("group" => $this->pi_uid)
            );

            // send email to the PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_removed_owner",
                array(
                    "group" => $this->pi_uid,
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

        $this->addRequest($new_user->getUID());

        if ($send_mail) {
            // send email to user
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_request",
                array("group" => $this->pi_uid)
            );

            // send email to PI
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_request_owner",
                array(
                    "group" => $this->pi_uid,
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
        $requests = $this->SQL->getRequests($this->pi_uid);

        $out = array();
        foreach ($requests as $request) {
            $user = new UnityUser($request["uid"], $this->LDAP, $this->SQL, $this->MAILER, $this->REDIS);
            array_push($out, [$user, $request["timestamp"]]);
        }

        return $out;
    }

    public function getGroupMembers($ignorecache = false)
    {
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->getPIUID(), "members");
            if (!is_null($cached_val)) {
                $members = $cached_val;
            }
        }

        if (!isset($members)) {
            $pi_group = $this->getLDAPPiGroup();
            $members = $pi_group->getAttribute("memberuid");
        }

        $out = array();
        $owner_uid = $this->getOwner()->getUID();
        foreach ($members as $member) {
            if ($member != $owner_uid) {
                array_push($out, new UnityUser($member, $this->LDAP, $this->SQL, $this->MAILER, $this->REDIS));
            }
        }

        return $out;
    }

    public function getGroupMemberUIDs()
    {
        $pi_group = $this->getLDAPPiGroup();
        $members = $pi_group->getAttribute("memberuid");

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
        $ldapPiGroupEntry = $this->getLDAPPiGroup();

        if (!$ldapPiGroupEntry->exists()) {
            $nextGID = $this->LDAP->getNextPiGIDNumber();

            $ldapPiGroupEntry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $ldapPiGroupEntry->setAttribute("gidnumber", strval($nextGID));
            $ldapPiGroupEntry->setAttribute("memberuid", array($owner->getUID()));

            if (!$ldapPiGroupEntry->write()) {
                throw new Exception("Failed to create POSIX group for " . $owner->getUID());  // this shouldn't execute
            }
        }

        $cached_val = $this->REDIS->getCache("sorted_groups", "");
        if (is_null($cached_val)) {
            $this->REDIS->setCache("sorted_groups", "", array($this->getPIUID()));
        } else {
            array_push($cached_val, $this->getPIUID());
            sort($cached_val);
            $this->REDIS->setCache("sorted_groups", "", $cached_val);
        }

        // TODO if we ever make this project based, we need to update the cache here with the memberuid
    }

    private function addUserToGroup($new_user)
    {
        // Add to LDAP Group
        $pi_group = $this->getLDAPPiGroup();
        $pi_group->appendAttribute("memberuid", $new_user->getUID());

        if (!$pi_group->write()) {
            throw new Exception("Unable to write PI group");
        }

        $cached_val = $this->REDIS->getCache($this->getPIUID(), "members");
        if (is_null($cached_val)) {
            $this->REDIS->setCache($this->getPIUID(), "members", array($new_user->getUID()));
        } else {
            array_push($cached_val, $new_user->getUID());
            $this->REDIS->setCache($this->getPIUID(), "members", $cached_val);
        }
    }

    private function removeUserFromGroup($old_user)
    {
        // Remove from LDAP Group
        $pi_group = $this->getLDAPPiGroup();
        $pi_group->removeAttributeEntryByValue("memberuid", $old_user->getUID());

        if (!$pi_group->write()) {
            throw new Exception("Unable to write PI group");
        }

        $cached_val = $this->REDIS->getCache($this->getPIUID(), "members");
        if (is_null($cached_val)) {
            $this->REDIS->setCache($this->getPIUID(), "members", array());
        } else {
            $cached_val = array_diff($cached_val, $old_user->getUID());
            $this->REDIS->setCache($this->getPIUID(), "members", $cached_val);
        }
    }

    public function userExists($user)
    {
        return in_array($user->getUID(), $this->getGroupMemberUIDs());
    }

    private function addRequest($uid)
    {
        $this->SQL->addRequest($uid, $this->pi_uid);
    }

    private function removeRequest($uid)
    {
        $this->SQL->removeRequest($uid, $this->pi_uid);
    }

    //
    // Public helper functions
    //

    public function getOwner()
    {
        return new UnityUser(self::getUIDfromPIUID($this->pi_uid), $this->LDAP, $this->SQL, $this->MAILER, $this->REDIS);
    }

    public function getLDAPPiGroup()
    {
        return $this->LDAP->getPIGroupEntry($this->pi_uid);
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
