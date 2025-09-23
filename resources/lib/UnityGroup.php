<?php

namespace UnityWebPortal\lib;

use Exception;

/**
 * Class that represents a single PI group in the Unity Cluster.
 */
class UnityGroup
{
    public const PI_PREFIX = "pi_";

    public $gid;
    private $entry;

    private $LDAP;
    private $SQL;
    private $MAILER;
    private $WEBHOOK;
    private $REDIS;

    /**
     * Constructor for the object
     *
     * @param string $gid  PI UID in the format <PI_PREFIX><OWNER_UID>
     * @param LDAP   $LDAP LDAP Connection
     * @param SQL    $SQL  SQL Connection
     */
    public function __construct($gid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK)
    {
        $gid = trim($gid);
        $this->gid = $gid;
        $this->entry = $LDAP->getPIGroupEntry($gid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->REDIS = $REDIS;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function equals($other_group)
    {
        if (!is_a($other_group, self::class)) {
            throw new Exception(
                "Unable to check equality because the parameter is not a " . self::class . " object"
            );
        }

        return $this->gid == $other_group->gid;
    }

    public function __toString()
    {
        return $this->gid;
    }

    /**
     * Checks if the current PI is an approved and existent group
     *
     * @return bool true if yes, false if no
     */
    public function exists()
    {
        return $this->entry->exists();
    }

    public function requestGroup(
        $firstname,
        $lastname,
        $email,
        $org,
        $send_mail_to_admins,
        $send_mail = true
    ) {
        if ($this->exists()) {
            return;
        }
        if ($this->SQL->accDeletionRequestExists($this->getOwner()->uid)) {
            return;
        }
        $this->SQL->addRequest($this->getOwner()->uid, $firstname, $lastname, $email, $org);
        if ($send_mail) {
            $this->MAILER->sendMail(
                $email,
                "group_request"
            );
            $this->WEBHOOK->sendWebhook(
                "group_request_admin",
                array(
                    "user" => $this->getOwner()->uid,
                    "org" => $org,
                    "name" => "$firstname $lastname",
                    "email" => $email
                )
            );
            if ($send_mail_to_admins) {
                $this->MAILER->sendMail(
                    "admin",
                    "group_request_admin",
                    array(
                        "user" => $this->getOwner()->uid,
                        "org" => $org,
                        "name" => "$firstname $lastname",
                        "email" => $email
                    )
                );
            }
            $this->MAILER->sendMail(
                "pi_approve",
                "group_request_admin",
                array(
                    "user" => $this->getOwner()->uid,
                    "org" => $org,
                    "name" => "$firstname $lastname",
                    "email" => $email
                )
            );
        }
    }

    /**
     * This method will create the group (this is what is executed when an admin approved the group)
     */
    public function approveGroup($operator = null, $send_mail = true)
    {
        $uid = $this->getOwner()->uid;
        $request = $this->SQL->getRequest($uid, UnitySQL::REQUEST_BECOME_PI);
        if ($this->exists()) {
            return;
        }
        if (!$this->getOwner()->exists()) {
            $this->getOwner()->init(
                $request["firstname"],
                $request["lastname"],
                $request["email"],
                $request["org"],
                $send_mail
            );
        }
        $this->init();
        $this->SQL->removeRequest($this->getOwner()->uid);
        $operator = is_null($operator) ? $this->getOwner()->uid : $operator->uid;
        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "approved_group",
            $this->getOwner()->uid
        );
        if ($send_mail) {
            $this->MAILER->sendMail(
                $request["email"],
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
        $this->SQL->removeRequest($this->getOwner()->uid);
        if ($this->exists()) {
            return;
        }
        $operator = is_null($operator) ? $this->getOwner()->uid : $operator->uid;
        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "denied_group",
            $this->getOwner()->uid
        );
        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_denied"
            );
        }
    }

    public function cancelGroupRequest($send_mail = true)
    {
        if (!$this->SQL->requestExists($this->getOwner()->uid)) {
            return;
        }
        $this->SQL->removeRequest($this->getOwner()->uid);
        if ($send_mail) {
            $this->MAILER->sendMail(
                "admin",
                "group_request_cancelled",
                ["uid" => $this->getOwner()->uid],
            );
        }
    }

    public function cancelGroupJoinRequest($user, $send_mail = true)
    {
        if (!$this->requestExists($user)) {
            return;
        }
        $this->SQL->removeRequest($user->uid, $this->gid);
        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_join_request_cancelled",
                ["uid" => $user->uid]
            );
        }
    }

    // /**
    //  * This method will delete the group, either by admin action or PI action
    //  */
    // public function removeGroup($send_mail = true)
    // {
    //     // remove any pending requests
    //     // this will silently fail if the request doesn't exist (which is what we want)
    //     $this->SQL->removeRequests($this->gid);

    //     // we don't need to do anything extra if the group is already deleted
    //     if (!$this->exists()) {
    //         return;
    //     }

    //     // first, we must record the users in the group currently
    //     $users = $this->getGroupMembers();

    //     // now we delete the ldap entry
    //     \ensure($this->entry->exists());
    //     $this->entry->delete();
    //     $this->REDIS->removeCacheArray("sorted_groups", "", $this->gid);
    //     foreach ($users as $user) {
    //         $this->REDIS->removeCacheArray($user->uid, "groups", $this->gid);
    //     }

    //     // send email to every user of the now deleted PI group
    //     if ($send_mail) {
    //         foreach ($users as $user) {
    //             $this->MAILER->sendMail(
    //                 $user->getMail(),
    //                 "group_disband",
    //                 array("group_name" => $this->gid)
    //             );
    //         }
    //     }
    // }

    /**
     * This method is executed when a user is approved to join the group
     * (either by admin or the group owner)
     */
    public function approveUser($new_user, $send_mail = true)
    {
        $request = $this->SQL->getRequest($new_user->uid, $this->gid);
        if (!$new_user->exists()) {
            $new_user->init(
                $request["firstname"],
                $request["lastname"],
                $request["email"],
                $request["org"],
            );
        }
        $this->addUserToGroup($new_user);
        $this->SQL->removeRequest($new_user->uid, $this->gid);
        if ($send_mail) {
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_added",
                array("group" => $this->gid)
            );
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_added_owner",
                array(
                    "group" => $this->gid,
                    "user" => $new_user->uid,
                    "name" => $request["firstname"] . " " . $request["lastname"],
                    "email" => $request["email"],
                    "org" => $request["org"],
                )
            );
        }
    }

    public function denyUser($new_user, $send_mail = true)
    {
        $request = $this->SQL->getRequest($new_user->uid, $this->gid);
        // remove request, this will fail silently if the request doesn't exist
        $this->SQL->removeRequest($new_user->uid, $this->gid);
        if ($send_mail) {
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_denied",
                array("group" => $this->gid)
            );
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_denied_owner",
                array(
                    "group" => $this->gid,
                    "user" => $new_user->uid,
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
        if ($new_user->uid == $this->getOwner()->uid) {
            throw new Exception("Cannot delete group owner from group. Disband group instead");
        }
        // remove request, this will fail silently if the request doesn't exist
        $this->removeUserFromGroup($new_user);
        if ($send_mail) {
            $this->MAILER->sendMail(
                $new_user->getMail(),
                "group_user_removed",
                array("group" => $this->gid)
            );
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_removed_owner",
                array(
                    "group" => $this->gid,
                    "user" => $new_user->uid,
                    "name" => $new_user->getFullName(),
                    "email" => $new_user->getMail(),
                    "org" => $new_user->getOrg()
                )
            );
        }
    }

    public function newUserRequest(
        $new_user,
        $firstname,
        $lastname,
        $email,
        $org,
        $send_mail = true
    ) {
        if ($this->userExists($new_user)) {
            UnitySite::errorLog("warning", "user '$new_user' already in group");
            return;
        }
        if ($this->requestExists($new_user)) {
            UnitySite::errorLog("warning", "user '$new_user' already requested group membership");
            return;
        }
        if ($this->SQL->accDeletionRequestExists($new_user->uid)) {
            throw new Exception("user '$new_user' requested account deletion");
            return;
        }
        $this->addRequest($new_user->uid, $firstname, $lastname, $email, $org);
        if ($send_mail) {
            $this->MAILER->sendMail(
                $email,
                "group_user_request",
                array("group" => $this->gid)
            );
            $this->MAILER->sendMail(
                $this->getOwner()->getMail(),
                "group_user_request_owner",
                array(
                    "group" => $this->gid,
                    "user" => $new_user->uid,
                    "name" => "$firstname $lastname",
                    "email" => $email,
                    "org" => $org,
                )
            );
        }
    }

    public function getRequests()
    {
        $requests = $this->SQL->getRequests($this->gid);
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
            array_push(
                $out,
                [
                    $user,
                    $request["timestamp"],
                    $request["firstname"],
                    $request["lastname"],
                    $request["email"],
                    $request["org"],
                ]
            );
        }
        return $out;
    }

    public function getGroupMembers($ignorecache = false)
    {
        $members = $this->getGroupMemberUIDs($ignorecache);
        $out = array();
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
        }
        return $out;
    }

    public function getGroupMemberUIDs($ignorecache = false)
    {
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->gid, "members");
            if (!is_null($cached_val)) {
                $members = $cached_val;
            }
        }
        $updatecache = false;
        if (!isset($members)) {
            $members = $this->entry->getAttribute("memberuid");
            $updatecache = true;
        }
        if (!$ignorecache && $updatecache) {
            sort($members);
            $this->REDIS->setCache($this->gid, "members", $members);
        }
        return $members;
    }

    public function requestExists($user)
    {
        $requesters = $this->getRequests();
        if (count($requesters) > 0) {
            foreach ($requesters as $requester) {
                if ($requester[0]->uid == $user->uid) {
                    return true;
                }
            }
        }
        return false;
    }

    private function init()
    {
        $owner = $this->getOwner();
        \ensure(!$this->entry->exists());
        $nextGID = $this->LDAP->getNextPIGIDNumber();
        $this->entry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $this->entry->setAttribute("gidnumber", strval($nextGID));
        $this->entry->setAttribute("memberuid", array($owner->uid));
        $this->entry->write();
        $this->REDIS->appendCacheArray("sorted_groups", "", $this->gid);
        // TODO if we ever make this project based,
        // we need to update the cache here with the memberuid
    }

    private function addUserToGroup($new_user)
    {
        $this->entry->appendAttribute("memberuid", $new_user->uid);
        $this->entry->write();
        $this->REDIS->appendCacheArray($this->gid, "members", $new_user->uid);
        $this->REDIS->appendCacheArray($new_user->uid, "groups", $this->gid);
    }

    private function removeUserFromGroup($old_user)
    {
        $this->entry->removeAttributeEntryByValue("memberuid", $old_user->uid);
        $this->entry->write();
        $this->REDIS->removeCacheArray($this->gid, "members", $old_user->uid);
        $this->REDIS->removeCacheArray($old_user->uid, "groups", $this->gid);
    }

    public function userExists($user)
    {
        return in_array($user->uid, $this->getGroupMemberUIDs());
    }

    private function addRequest($uid, $firstname, $lastname, $email, $org)
    {
        $this->SQL->addRequest($uid, $firstname, $lastname, $email, $org, $this->gid);
    }

    public function getOwner()
    {
        return new UnityUser(
            self::GID2OwnerUID($this->gid),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->REDIS,
            $this->WEBHOOK
        );
    }

    public static function ownerUID2GID($uid)
    {
        return self::PI_PREFIX . $uid;
    }

    public static function GID2OwnerUID($gid)
    {
        if (substr($gid, 0, strlen(self::PI_PREFIX)) != self::PI_PREFIX) {
            throw new Exception("PI group GID doesn't have the correct prefix.");
        }
        return substr($gid, strlen(self::PI_PREFIX));
    }
}
