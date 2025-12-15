<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPEntry;
use Exception;

class UnityUser extends LDAPEntry
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
        parent::__construct($LDAP, $LDAP->getUserDN($uid));
        $uid = trim($uid);
        $this->uid = $uid;

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

        \ensure(!$this->exists());
        $this->setAttribute("objectclass", UnityLDAP::POSIX_ACCOUNT_CLASS);
        $this->setAttribute("uid", $this->uid);
        $this->setAttribute("givenname", $firstname);
        $this->setAttribute("sn", $lastname);
        $this->setAttribute(
            "gecos",
            \transliterator_transliterate("Latin-ASCII", "$firstname $lastname"),
        );
        $this->setAttribute("mail", $email);
        $this->setAttribute("o", $org);
        $this->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
        $this->setAttribute("loginshell", $this->LDAP->getDefUserShell());
        $this->setAttribute("uidnumber", strval($id));
        $this->setAttribute("gidnumber", strval($id));
        $this->write();

        $org = $this->getOrgGroup();
        if (!$org->exists()) {
            $org->init();
        }

        if (!$org->inOrg($this)) {
            $org->addUser($this);
        }

        $this->SQL->addLog($this->uid, $_SERVER["REMOTE_ADDR"], "user_added", $this->uid);
    }

    public function isQualified(): bool
    {
        return $this->LDAP->getQualifiedUserGroup()->attributeValueExists("memberUid", $this->uid);
    }

    public function setIsQualified(bool $newIsQualified, bool $doSendMail = true): void
    {
        $oldIsQualified = $this->isQualified();
        if ($oldIsQualified == $newIsQualified) {
            return;
        }
        if ($newIsQualified) {
            $this->LDAP->getQualifiedUserGroup()->appendAttribute("memberuid", $this->uid);
            $this->LDAP->getQualifiedUserGroup()->write();
            if ($doSendMail) {
                $this->MAILER->sendMail($this->getMail(), "user_qualified", [
                    "user" => $this->uid,
                    "org" => $this->getOrg(),
                ]);
            }
        } else {
            $this->LDAP
                ->getQualifiedUserGroup()
                ->removeAttributeEntryByValue("memberuid", $this->uid);
            $this->LDAP->getQualifiedUserGroup()->write();
            if ($doSendMail) {
                $this->MAILER->sendMail($this->getMail(), "user_dequalified", [
                    "user" => $this->uid,
                    "org" => $this->getOrg(),
                ]);
            }
        }
    }

    /**
     * Returns the ldap group entry corresponding to the user
     */
    public function getGroupEntry(): LDAPEntry
    {
        return new LDAPEntry($this->LDAP, $this->LDAP->getUserGroupDN($this->uid));
    }

    public function setOrg(UnityOrg $org): void
    {
        $this->setAttribute("o", $org);
        $this->write();
    }

    public function getOrg(): string
    {
        $this->ensureExists();
        return $this->getAttribute("o")[0];
    }

    /**
     * Sets the firstname of the account and the corresponding ldap entry if it exists
     */
    public function setFirstname(string $firstname, ?UnityUser $operator = null): void
    {
        $this->setAttribute("givenname", $firstname);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "firstname_changed", $this->uid);

        $this->write();
    }

    /**
     * Gets the firstname of the account
     */
    public function getFirstname(): string
    {
        $this->ensureExists();
        return $this->getAttribute("givenname")[0];
    }

    /**
     * Sets the lastname of the account and the corresponding ldap entry if it exists
     */
    public function setLastname(string $lastname, $operator = null): void
    {
        $this->setAttribute("sn", $lastname);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "lastname_changed", $this->uid);

        $this->write();
    }

    /**
     * Get method for the lastname on the account
     */
    public function getLastname(): string
    {
        $this->ensureExists();
        return $this->getAttribute("sn")[0];
    }

    public function getFullname(): string
    {
        $this->ensureExists();
        return $this->getFirstname() . " " . $this->getLastname();
    }

    /**
     * Sets the mail in the account and the ldap entry
     */
    public function setMail(string $email, ?UnityUser $operator = null): void
    {
        $this->setAttribute("mail", $email);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "email_changed", $this->uid);

        $this->write();
    }

    /**
     * Method to get the mail instance var
     */
    public function getMail(): string
    {
        $this->ensureExists();
        return $this->getAttribute("mail")[0];
    }

    /**
     * Sets the SSH keys on the account and the corresponding entry
     */
    public function setSSHKeys($keys, $operator = null, bool $send_mail = true): void
    {
        $operator = is_null($operator) ? $this->uid : $operator->uid;
        $keys_filt = array_values(array_unique($keys));
        \ensure($this->exists());
        $this->setAttribute("sshpublickey", $keys_filt);
        $this->write();

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
        $this->ensureExists();
        $result = $this->getAttribute("sshpublickey");
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
        \ensure($this->exists());
        $this->setAttribute("loginshell", $shell);
        $this->write();

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
        $this->ensureExists();
        return $this->getAttribute("loginshell")[0];
    }

    public function setHomeDir(string $home, ?UnityUser $operator = null): void
    {
        \ensure($this->exists());
        $this->setAttribute("homedirectory", $home);
        $this->write();
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog($operator, $_SERVER["REMOTE_ADDR"], "homedir_changed", $this->uid);
    }

    /**
     * Gets the home directory of the user
     */
    public function getHomeDir(): string
    {
        $this->ensureExists();
        return $this->getAttribute("homedirectory");
    }

    /**
     * Checks if the current account is an admin
     */
    public function isAdmin(): bool
    {
        $admins = $this->LDAP->getAdminGroup()->getAttribute("memberuid");
        return in_array($this->uid, $admins);
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

        return in_array($uid, $group_checked->getGroupMemberUIDs());
    }
}
