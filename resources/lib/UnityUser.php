<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPEntry;
use Exception;

class UnityUser
{
    private const HOME_DIR = "/home/";

    public string $uid;
    private LDAPEntry $entry;
    private UnityLDAP $LDAP;
    private UnitySQL $SQL;
    private UnityMailer $MAILER;
    private UnityWebhook $WEBHOOK;

    public function __construct(
        string $uid,
        UnityLDAP $LDAP,
        UnitySQL $SQL,
        UnityMailer $MAILER,
        UnityWebhook $WEBHOOK,
    ) {
        $uid = trim($uid);
        $this->uid = $uid;
        $this->entry = $LDAP->getUserEntry($uid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function equals(UnityUser $other_user): bool
    {
        if (!is_a($other_user, self::class)) {
            throw new Exception(
                "Unable to check equality because the parameter is not a " .
                    self::class .
                    " object",
            );
        }

        return $this->uid == $other_user->uid;
    }

    public function __toString(): string
    {
        return $this->uid;
    }

    /**
     * This is the method that is run when a new account is created
     */
    public function init(
        string $firstname,
        string $lastname,
        string $email,
        string $org,
        bool $send_mail = true,
    ): void {
        $ldapGroupEntry = $this->getGroupEntry();
        $id = $this->LDAP->getNextUIDGIDNumber($this->uid);
        \ensure(!$ldapGroupEntry->exists());
        $ldapGroupEntry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $ldapGroupEntry->setAttribute("gidnumber", strval($id));
        $ldapGroupEntry->write();

        \ensure(!$this->entry->exists());
        $this->entry->setAttribute("objectclass", UnityLDAP::POSIX_ACCOUNT_CLASS);
        $this->entry->setAttribute("uid", $this->uid);
        $this->entry->setAttribute("givenname", $firstname);
        $this->entry->setAttribute("sn", $lastname);
        $this->entry->setAttribute(
            "gecos",
            \transliterator_transliterate("Latin-ASCII", "$firstname $lastname"),
        );
        $this->entry->setAttribute("mail", $email);
        $this->entry->setAttribute("o", $org);
        $this->entry->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
        $this->entry->setAttribute("loginshell", $this->LDAP->getDefUserShell());
        $this->entry->setAttribute("uidnumber", strval($id));
        $this->entry->setAttribute("gidnumber", strval($id));
        $this->entry->write();

        $org = $this->getOrgGroup();
        if (!$org->exists()) {
            $org->init();
        }

        if (!$org->memberUIDExists($this->uid)) {
            $org->addMemberUID($this->uid);
        }

        $this->SQL->addLog($this->uid, $_SERVER["REMOTE_ADDR"], "user_added", $this->uid);
    }

    public function getModifier(string $modifier): bool
    {
        return $this->LDAP->userModifierGroups[$modifier]->memberUIDExists($this->uid);
    }

    public function setModifier(
        string $modifier,
        bool $newValue,
        bool $doSendMail = true,
        bool $doSendMailAdmin = true,
    ): void {
        $oldValue = $this->getModifier($modifier);
        if ($oldValue == $newValue) {
            return;
        }
        if ($newValue) {
            $this->LDAP->userModifierGroups[$modifier]->addMemberUID($this->uid);
            if ($doSendMail) {
                $this->MAILER->sendMail($this->getMail(), "user_modifier_added", [
                    "user" => $this->uid,
                    "org" => $this->getOrg(),
                    "modifier" => $modifier,
                ]);
            }
            if ($doSendMailAdmin) {
                $this->MAILER->sendMail("admin", "user_modifier_added_admin", [
                    "user" => $this->uid,
                    "org" => $this->getOrg(),
                    "modifier" => $modifier,
                ]);
            }
        } else {
            $this->LDAP->userModifierGroups[$modifier]->removeMemberUID($this->uid);
            if ($doSendMail) {
                $this->MAILER->sendMail($this->getMail(), "user_modifier_removed", [
                    "user" => $this->uid,
                    "org" => $this->getOrg(),
                    "modifier" => $modifier,
                ]);
            }
            if ($doSendMailAdmin) {
                $this->MAILER->sendMail("admin", "user_modifier_removed_admin", [
                    "user" => $this->uid,
                    "org" => $this->getOrg(),
                    "modifier" => $modifier,
                ]);
            }
        }
    }

    /**
     * Returns the ldap group entry corresponding to the user
     */
    public function getGroupEntry(): LDAPEntry
    {
        return $this->LDAP->getGroupEntry($this->uid);
    }

    public function exists(): bool
    {
        return $this->entry->exists() && $this->getGroupEntry()->exists();
    }

    public function setOrg(UnityOrg $org): void
    {
        $this->entry->setAttribute("o", $org);
        $this->entry->write();
    }

    public function getOrg(): string
    {
        $this->entry->ensureExists();
        return $this->entry->getAttribute("o")[0];
    }

    /**
     * Sets the firstname of the account and the corresponding ldap entry if it exists
     */
    public function setFirstname(string $firstname, ?UnityUser $operator = null): void
    {
        $this->entry->setAttribute("givenname", $firstname);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "firstname_changed", $this->uid);

        $this->entry->write();
    }

    /**
     * Gets the firstname of the account
     */
    public function getFirstname(): string
    {
        $this->entry->ensureExists();
        return $this->entry->getAttribute("givenname")[0];
    }

    /**
     * Sets the lastname of the account and the corresponding ldap entry if it exists
     */
    public function setLastname(string $lastname, $operator = null): void
    {
        $this->entry->setAttribute("sn", $lastname);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "lastname_changed", $this->uid);

        $this->entry->write();
    }

    /**
     * Get method for the lastname on the account
     */
    public function getLastname(): string
    {
        $this->entry->ensureExists();
        return $this->entry->getAttribute("sn")[0];
    }

    public function getFullname(): string
    {
        $this->entry->ensureExists();
        return $this->getFirstname() . " " . $this->getLastname();
    }

    /**
     * Sets the mail in the account and the ldap entry
     */
    public function setMail(string $email, ?UnityUser $operator = null): void
    {
        $this->entry->setAttribute("mail", $email);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "email_changed", $this->uid);

        $this->entry->write();
    }

    /**
     * Method to get the mail instance var
     */
    public function getMail(): string
    {
        $this->entry->ensureExists();
        return $this->entry->getAttribute("mail")[0];
    }

    /**
     * Sets the SSH keys on the account and the corresponding entry
     */
    public function setSSHKeys($keys, $operator = null, bool $send_mail = true): void
    {
        $operator = is_null($operator) ? $this->uid : $operator->uid;
        $keys_filt = array_values(array_unique($keys));
        \ensure($this->entry->exists());
        $this->entry->setAttribute("sshpublickey", $keys_filt);
        $this->entry->write();

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "sshkey_modify", $this->uid);

        if ($send_mail) {
            $this->MAILER->sendMail($this->getMail(), "user_sshkey", [
                "keys" => $this->getSSHKeys(),
            ]);
        }
    }

    /**
     * Returns the SSH keys attached to the account
     */
    public function getSSHKeys(): array
    {
        $this->entry->ensureExists();
        $result = $this->entry->getAttribute("sshpublickey");
        return $result;
    }

    /**
     * Sets the login shell for the account
     */
    public function setLoginShell(
        string $shell,
        ?UnityUser $operator = null,
        bool $send_mail = true,
    ): void {
        // ldap schema syntax is "IA5 String (1.3.6.1.4.1.1466.115.121.1.26)"
        if (!mb_check_encoding($shell, "ASCII")) {
            throw new Exception("non ascii characters are not allowed in a login shell!");
        }
        if ($shell != trim($shell)) {
            throw new Exception("leading/trailing whitespace is not allowed in a login shell!");
        }
        if (empty($shell)) {
            throw new Exception("login shell must not be empty!");
        }
        \ensure($this->entry->exists());
        $this->entry->setAttribute("loginshell", $shell);
        $this->entry->write();

        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "loginshell_changed", $this->uid);

        if ($send_mail) {
            $this->MAILER->sendMail($this->getMail(), "user_loginshell", [
                "new_shell" => $this->getLoginShell(),
            ]);
        }
    }

    /**
     * Gets the login shell of the account
     */
    public function getLoginShell(): string
    {
        $this->entry->ensureExists();
        return $this->entry->getAttribute("loginshell")[0];
    }

    public function setHomeDir(string $home, ?UnityUser $operator = null): void
    {
        \ensure($this->entry->exists());
        $this->entry->setAttribute("homedirectory", $home);
        $this->entry->write();
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "homedir_changed", $this->uid);
    }

    /**
     * Gets the home directory of the user
     */
    public function getHomeDir(): string
    {
        $this->entry->ensureExists();
        return $this->entry->getAttribute("homedirectory");
    }

    /**
     * Checks if current user is a PI
     */
    public function isPI(): bool
    {
        return $this->getPIGroup()->exists();
    }

    public function getPIGroup(): UnityGroup
    {
        return new UnityGroup(
            UnityGroup::ownerUID2GID($this->uid),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->WEBHOOK,
        );
    }

    public function getOrgGroup(): UnityOrg
    {
        return new UnityOrg(
            $this->getOrg(),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->WEBHOOK,
        );
    }

    /**
     * Gets the groups this user is assigned to, can be more than one
     */
    public function getPIGroupGIDs(): array
    {
        return $this->LDAP->getPIGroupGIDsWithMemberUID($this->uid);
    }

    /**
     * Sends an email to admins about account deletion request
     * and also adds it to a table in the database
     */
    public function requestAccountDeletion(): void
    {
        $this->SQL->deleteRequestsByUser($this->uid);
        $this->SQL->addAccountDeletionRequest($this->uid);
        $this->MAILER->sendMail("admin", "account_deletion_request_admin", [
            "user" => $this->uid,
            "name" => $this->getFullname(),
            "email" => $this->getMail(),
        ]);
    }

    public function cancelRequestAccountDeletion(): void
    {
        $this->SQL->deleteAccountDeletionRequest($this->uid);
        $this->MAILER->sendMail("admin", "account_deletion_request_cancelled_admin", [
            "user" => $this->uid,
            "name" => $this->getFullname(),
            "email" => $this->getMail(),
        ]);
    }

    /**
     * Checks if the user has requested account deletion
     */
    public function hasRequestedAccountDeletion(): bool
    {
        return $this->SQL->accDeletionRequestExists($this->uid);
    }

    /**
     * Checks whether a user is in a group or not
     */
    public function isInGroup(string $uid, UnityGroup $group): bool
    {
        if (gettype($group) == "string") {
            $group_checked = new UnityGroup(
                $group,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->WEBHOOK,
            );
        } else {
            $group_checked = $group;
        }

        return in_array($uid, $group_checked->getMemberUIDs());
    }
}
