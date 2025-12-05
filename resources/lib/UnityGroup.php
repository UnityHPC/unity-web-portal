<?php

namespace UnityWebPortal\lib;
use PHPOpenLDAPer\LDAPEntry;

use Exception;

/**
 * Class that represents a single PI group in the Unity Cluster.
 */
class UnityGroup
{
    public const string PI_PREFIX = "pi_";

    public string $gid;
    private LDAPEntry $entry;
    private UnityLDAP $LDAP;
    private UnitySQL $SQL;
    private UnityMailer $MAILER;
    private UnityWebhook $WEBHOOK;

    public function __construct(
        string $gid,
        UnityLDAP $LDAP,
        UnitySQL $SQL,
        UnityMailer $MAILER,
        UnityWebhook $WEBHOOK,
    ) {
        $gid = trim($gid);
        $this->gid = $gid;
        $this->entry = $LDAP->getPIGroupEntry($gid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function equals(UnityGroup $other_group): bool
    {
        if (!is_a($other_group, self::class)) {
            throw new Exception(
                "Unable to check equality because the parameter is not a " .
                    self::class .
                    " object",
            );
        }

        return $this->gid == $other_group->gid;
    }

    public function __toString(): string
    {
        return $this->gid;
    }

    /**
     * Checks if the current PI is an approved and existent group
     */
    public function exists(): bool
    {
        return $this->entry->exists();
    }

    public function requestGroup(bool $send_mail_to_admins, bool $send_mail = true): void
    {
        if ($this->exists()) {
            return;
        }
        if ($this->SQL->accDeletionRequestExists($this->getOwner()->uid)) {
            return;
        }
        $context = [
            "user" => $this->getOwner()->uid,
            "org" => $this->getOwner()->getOrg(),
            "name" => $this->getOwner()->getFullName(),
            "email" => $this->getOwner()->getMail(),
        ];
        $this->SQL->addRequest($this->getOwner()->uid, "admin");
        if ($send_mail) {
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_request");
            $this->WEBHOOK->sendWebhook("group_request_admin", $context);
            if ($send_mail_to_admins) {
                $this->MAILER->sendMail("admin", "group_request_admin", $context);
            }
            $this->MAILER->sendMail("pi_approve", "group_request_admin", $context);
        }
    }

    /**
     * This method will create the group (this is what is executed when an admin approved the group)
     */
    public function approveGroup(?UnityUser $operator = null, bool $send_mail = true): void
    {
        $uid = $this->getOwner()->uid;
        $request = $this->SQL->getRequest($uid, "admin");
        if ($this->exists()) {
            return;
        }
        \ensure($this->getOwner()->exists());
        $this->init();
        $this->SQL->removeRequest($this->getOwner()->uid, "admin");
        $operator = is_null($operator) ? $this->getOwner()->uid : $operator->uid;
        $this->SQL->addLog(
            $operator,
            $_SERVER["REMOTE_ADDR"],
            "approved_group",
            $this->getOwner()->uid,
        );
        if ($send_mail) {
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_created");
        }
        $this->getOwner()->setIsQualified(true); // having your own group makes you qualified
    }

    /**
     * This method is executed when an admin denys the PI group request
     */
    public function denyGroup(?UnityUser $operator = null, bool $send_mail = true): void
    {
        $request = $this->SQL->getRequest($this->getOwner()->uid, UnitySQL::REQUEST_BECOME_PI);
        $this->SQL->removeRequest($this->getOwner()->uid);
        if ($this->exists()) {
            return;
        }
        $operator = is_null($operator) ? $this->getOwner()->uid : $operator->uid;
        $this->SQL->addLog(
            $operator,
            $_SERVER["REMOTE_ADDR"],
            "denied_group",
            $this->getOwner()->uid,
        );
        if ($send_mail) {
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_denied");
        }
    }

    public function cancelGroupRequest(bool $send_mail = true): void
    {
        if (!$this->SQL->requestExists($this->getOwner()->uid, "admin")) {
            return;
        }
        $this->SQL->removeRequest($this->getOwner()->uid, "admin");
        if ($send_mail) {
            $this->MAILER->sendMail("admin", "group_request_cancelled", [
                "uid" => $this->getOwner()->uid,
            ]);
        }
    }

    public function cancelGroupJoinRequest(UnityUser $user, bool $send_mail = true): void
    {
        if (!$this->requestExists($user)) {
            return;
        }
        $this->SQL->removeRequest($user->uid, $this->gid);
        if ($send_mail) {
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_join_request_cancelled", [
                "uid" => $user->uid,
            ]);
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
    //     $this->entry->ensureExists();
    //     $this->entry->delete();

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
    public function approveUser(UnityUser $new_user, bool $send_mail = true): void
    {
        $request = $this->SQL->getRequest($new_user->uid, $this->gid);
        \ensure($new_user->exists());
        $this->addUserToGroup($new_user);
        $this->SQL->removeRequest($new_user->uid, $this->gid);
        if ($send_mail) {
            $this->MAILER->sendMail($new_user->getMail(), "group_user_added", [
                "group" => $this->gid,
            ]);
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_user_added_owner", [
                "group" => $this->gid,
                "user" => $new_user->uid,
                "name" => $new_user->getFullname(),
                "email" => $new_user->getMail(),
                "org" => $new_user->getOrg(),
            ]);
        }
        $new_user->setIsQualified(true); // being in a group makes you qualified
    }

    public function denyUser(UnityUser $new_user, bool $send_mail = true): void
    {
        $request = $this->SQL->getRequest($new_user->uid, $this->gid);
        // remove request, this will fail silently if the request doesn't exist
        $this->SQL->removeRequest($new_user->uid, $this->gid);
        if ($send_mail) {
            $this->MAILER->sendMail($new_user->getMail(), "group_user_denied", [
                "group" => $this->gid,
            ]);
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_user_denied_owner", [
                "group" => $this->gid,
                "user" => $new_user->uid,
                "name" => $new_user->getFullName(),
                "email" => $new_user->getMail(),
                "org" => $new_user->getOrg(),
            ]);
        }
    }

    public function removeUser(UnityUser $new_user, bool $send_mail = true): void
    {
        if (!$this->memberExists($new_user)) {
            return;
        }
        if ($new_user->uid == $this->getOwner()->uid) {
            throw new Exception("Cannot delete group owner from group. Disband group instead");
        }
        // remove request, this will fail silently if the request doesn't exist
        $this->removeUserFromGroup($new_user);
        if ($send_mail) {
            $this->MAILER->sendMail($new_user->getMail(), "group_user_removed", [
                "group" => $this->gid,
            ]);
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_user_removed_owner", [
                "group" => $this->gid,
                "user" => $new_user->uid,
                "name" => $new_user->getFullName(),
                "email" => $new_user->getMail(),
                "org" => $new_user->getOrg(),
            ]);
        }
    }

    public function newUserRequest(UnityUser $new_user, bool $send_mail = true): void
    {
        if ($this->memberExists($new_user)) {
            UnityHTTPD::errorLog("warning", "user '$new_user' already in group");
            return;
        }
        if ($this->requestExists($new_user)) {
            UnityHTTPD::errorLog("warning", "user '$new_user' already requested group membership");
            return;
        }
        if ($this->SQL->accDeletionRequestExists($new_user->uid)) {
            throw new Exception("user '$new_user' requested account deletion");
            return;
        }
        $this->addRequest($new_user->uid);
        if ($send_mail) {
            $this->MAILER->sendMail($new_user->getMail(), "group_user_request", [
                "group" => $this->gid,
            ]);
            $this->MAILER->sendMail($this->getOwner()->getMail(), "group_user_request_owner", [
                "group" => $this->gid,
                "user" => $new_user->uid,
                "name" => $new_user->getFullname(),
                "email" => $new_user->getMail(),
                "org" => $new_user->getOrg(),
            ]);
        }
    }

    public function getRequests(): array
    {
        $requests = $this->SQL->getRequests($this->gid);
        $out = [];
        foreach ($requests as $request) {
            $user = new UnityUser(
                $request["uid"],
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->WEBHOOK,
            );
            array_push($out, [$user, $request["timestamp"]]);
        }
        return $out;
    }

    public function getGroupMembers(): array
    {
        $members = $this->getGroupMemberUIDs();
        $out = [];
        foreach ($members as $member) {
            $user_obj = new UnityUser(
                $member,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->WEBHOOK,
            );
            array_push($out, $user_obj);
        }
        return $out;
    }

    public function getGroupMemberUIDs(): array
    {
        $members = $this->entry->getAttribute("memberuid");
        sort($members);
        return $members;
    }

    public function requestExists(UnityUser $user): bool
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

    private function init(): void
    {
        $owner = $this->getOwner();
        \ensure(!$this->entry->exists());
        $nextGID = $this->LDAP->getNextPIGIDNumber();
        $this->entry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $this->entry->setAttribute("gidnumber", strval($nextGID));
        $this->entry->setAttribute("memberuid", [$owner->uid]);
        $this->entry->write();
        // TODO if we ever make this project based,
        // we need to update the cache here with the memberuid
    }

    private function addUserToGroup(UnityUser $new_user): void
    {
        $this->entry->appendAttribute("memberuid", $new_user->uid);
        $this->entry->write();
    }

    private function removeUserFromGroup(UnityUser $old_user): void
    {
        $this->entry->removeAttributeEntryByValue("memberuid", $old_user->uid);
        $this->entry->write();
    }

    public function memberExists(UnityUser $user): bool
    {
        return in_array($user->uid, $this->getGroupMemberUIDs());
    }

    private function addRequest(string $uid): void
    {
        $this->SQL->addRequest($uid, $this->gid);
    }

    public function getOwner(): UnityUser
    {
        return new UnityUser(
            self::GID2OwnerUID($this->gid),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->WEBHOOK,
        );
    }

    public static function ownerUID2GID(string $uid): string
    {
        return self::PI_PREFIX . $uid;
    }

    public static function GID2OwnerUID(string $gid): string
    {
        if (substr($gid, 0, strlen(self::PI_PREFIX)) != self::PI_PREFIX) {
            throw new Exception("PI group GID doesn't have the correct prefix.");
        }
        return substr($gid, strlen(self::PI_PREFIX));
    }

    /**
     * @throws \UnityWebPortal\lib\exceptions\EntryNotFoundException
     */
    public static function ownerMail2GID(string $email): string
    {
        global $LDAP;
        $entry = $LDAP->getUidFromEmail($email); // throws EntryNotFoundException
        $ownerUid = $entry->getAttribute("cn")[0];
        return self::PI_PREFIX . $ownerUid;
    }

    public function getGroupMembersAttributes(array $attributes, array $default_values = []): array
    {
        return $this->LDAP->getUsersAttributes(
            $this->getGroupMemberUIDs(),
            $attributes,
            $default_values,
        );
    }
}
