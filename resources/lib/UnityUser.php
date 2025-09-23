<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPEntry;
use Exception;

class UnityUser
{
    private const HOME_DIR = "/home/";

    public $uid;
    private $entry;

    private $LDAP;
    private $SQL;
    private $MAILER;
    private $REDIS;
    private $WEBHOOK;

    public function __construct($uid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK)
    {
        $uid = trim($uid);
        $this->uid = $uid;
        $this->entry = $LDAP->getUserEntry($uid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->REDIS = $REDIS;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function equals($other_user)
    {
        if (!is_a($other_user, self::class)) {
            throw new Exception(
                "Unable to check equality because the parameter is not a " . self::class . " object"
            );
        }

        return $this->uid == $other_user->uid;
    }

    public function __toString()
    {
        return $this->uid;
    }

    /**
     * This is the method that is run when a new account is created
     *
     * @param  string $firstname First name of new account
     * @param  string $lastname  Last name of new account
     * @param  string $email     email of new account
     * @param  string $org       organization name of new account
     * @param  bool   $isPI      boolean value for if the user checked the "I am a PI box"
     * @return void
     */
    public function init($firstname, $lastname, $email, $org, $send_mail = true)
    {
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
            \transliterator_transliterate("Latin-ASCII", "$firstname $lastname")
        );
        $this->entry->setAttribute("mail", $email);
        $this->entry->setAttribute("o", $org);
        $this->entry->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
        $this->entry->setAttribute("loginshell", $this->LDAP->getDefUserShell());
        $this->entry->setAttribute("uidnumber", strval($id));
        $this->entry->setAttribute("gidnumber", strval($id));
        $this->entry->write();

        $this->REDIS->setCache($this->uid, "firstname", $firstname);
        $this->REDIS->setCache($this->uid, "lastname", $lastname);
        $this->REDIS->setCache($this->uid, "mail", $email);
        $this->REDIS->setCache($this->uid, "org", $org);
        $this->REDIS->setCache($this->uid, "homedir", self::HOME_DIR . $this->uid);
        $this->REDIS->setCache($this->uid, "loginshell", $this->LDAP->getDefUserShell());
        $this->REDIS->setCache($this->uid, "sshkeys", array());

        $org = $this->getOrgGroup();
        if (!$org->exists()) {
            $org->init();
        }

        if (!$org->inOrg($this)) {
            $org->addUser($this);
        }

        $this->LDAP->getUserGroup()->appendAttribute("memberuid", $this->uid);
        $this->LDAP->getUserGroup()->write();

        $this->REDIS->appendCacheArray("sorted_users", "", $this->uid);

        $this->SQL->addLog(
            $this->uid,
            $_SERVER['REMOTE_ADDR'],
            "user_added",
            $this->uid
        );

        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getMail(),
                "user_created",
                array("user" => $this->uid, "org" => $this->getOrg())
            );
        }
    }

    /**
     * Returns the ldap group entry corresponding to the user
     *
     * @return ldapEntry posix group
     */
    public function getGroupEntry()
    {
        return $this->LDAP->getGroupEntry($this->uid);
    }

    public function exists()
    {
        return $this->entry->exists() && $this->getGroupEntry()->exists();
    }

    public function setOrg($org)
    {
        $this->entry->setAttribute("o", $org);
        $this->entry->write();
        $this->REDIS->setCache($this->uid, "org", $org);
    }

    public function getOrg($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "org");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $org = $this->entry->getAttribute("o")[0];

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "org", $org);
            }

            return $this->entry->getAttribute("o")[0];
        }

        return null;
    }

    /**
     * Sets the firstname of the account and the corresponding ldap entry if it exists
     *
     * @param string $firstname
     */
    public function setFirstname($firstname, $operator = null)
    {
        $this->entry->setAttribute("givenname", $firstname);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "firstname_changed",
            $this->uid
        );

        $this->entry->write();
        $this->REDIS->setCache($this->uid, "firstname", $firstname);
    }

    /**
     * Gets the firstname of the account
     *
     * @return string firstname
     */
    public function getFirstname($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "firstname");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $firstname = $this->entry->getAttribute("givenname")[0];

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "firstname", $firstname);
            }

            return $firstname;
        }

        return null;
    }

    /**
     * Sets the lastname of the account and the corresponding ldap entry if it exists
     *
     * @param string $lastname
     */
    public function setLastname($lastname, $operator = null)
    {
        $this->entry->setAttribute("sn", $lastname);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "lastname_changed",
            $this->uid
        );

        $this->entry->write();
        $this->REDIS->setCache($this->uid, "lastname", $lastname);
    }

    /**
     * Get method for the lastname on the account
     *
     * @return string lastname
     */
    public function getLastname($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "lastname");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $lastname = $this->entry->getAttribute("sn")[0];

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "lastname", $lastname);
            }

            return $lastname;
        }

        return null;
    }

    public function getFullname()
    {
        \ensure($this->exists());
        return $this->getFirstname() . " " . $this->getLastname();
    }

    /**
     * Sets the mail in the account and the ldap entry
     *
     * @param string $mail
     */
    public function setMail($email, $operator = null)
    {
        $this->entry->setAttribute("mail", $email);
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "email_changed",
            $this->uid
        );

        $this->entry->write();
        $this->REDIS->setCache($this->uid, "mail", $email);
    }

    /**
     * Method to get the mail instance var
     *
     * @return string email address
     */
    public function getMail($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "mail");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $mail = $this->entry->getAttribute("mail")[0];

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "mail", $mail);
            }

            return $mail;
        }

        return null;
    }

    /**
     * Sets the SSH keys on the account and the corresponding entry
     *
     * @param array $keys String array of openssh-style ssh public keys
     */
    public function setSSHKeys($keys, $operator = null, $send_mail = true)
    {
        $operator = is_null($operator) ? $this->uid : $operator->uid;
        $keys_filt = array_values(array_unique($keys));
        \ensure($this->entry->exists());
        $this->entry->setAttribute("sshpublickey", $keys_filt);
        $this->entry->write();

        $this->REDIS->setCache($this->uid, "sshkeys", $keys_filt);

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "sshkey_modify",
            $this->uid
        );

        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getMail(),
                "user_sshkey",
                array("keys" => $this->getSSHKeys())
            );
        }
    }

    /**
     * Returns the SSH keys attached to the account
     *
     * @return array String array of ssh keys
     */
    public function getSSHKeys($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "sshkeys");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $result = $this->entry->getAttribute("sshpublickey");
            if (is_null($result)) {
                $keys = array();
            } else {
                $keys = $result;
            }

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "sshkeys", $keys);
            }

            return $keys;
        }

        return null;
    }

    /**
     * Sets the login shell for the account
     *
     * @param string $shell absolute path to shell
     */
    public function setLoginShell($shell, $operator = null, $send_mail = true)
    {
        // ldap schema syntax is "IA5 String (1.3.6.1.4.1.1466.115.121.1.26)"
        if (!mb_check_encoding($shell, 'ASCII')) {
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

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "loginshell_changed",
            $this->uid
        );

        $this->REDIS->setCache($this->uid, "loginshell", $shell);

        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getMail(),
                "user_loginshell",
                array("new_shell" => $this->getLoginShell())
            );
        }
    }

    /**
     * Gets the login shell of the account
     *
     * @return string absolute path to login shell
     */
    public function getLoginShell($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "loginshell");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $loginshell = $this->entry->getAttribute("loginshell")[0];

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "loginshell", $loginshell);
            }

            return $loginshell;
        }

        return null;
    }

    public function setHomeDir($home, $operator = null)
    {
        \ensure($this->entry->exists());
        $this->entry->setAttribute("homedirectory", $home);
        $this->entry->write();
        $operator = is_null($operator) ? $this->uid : $operator->uid;

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "homedir_changed",
            $this->uid
        );

        $this->REDIS->setCache($this->uid, "homedir", $home);
    }

    /**
     * Gets the home directory of the user
     *
     * @return string home directory of the user
     */
    public function getHomeDir($ignorecache = false)
    {
        \ensure($this->exists());
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "homedir");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }

        if ($this->exists()) {
            $homedir = $this->entry->getAttribute("homedirectory");

            if (!$ignorecache) {
                $this->REDIS->setCache($this->uid, "homedir", $homedir);
            }

            return $homedir;
        }

        return null;
    }

    /**
     * Checks if the current account is an admin
     *
     * @return boolean true if admin, false if not
     */
    public function isAdmin()
    {
        $admins = $this->LDAP->getAdminGroup()->getAttribute("memberuid");
        return in_array($this->uid, $admins);
    }

    /**
     * Checks if current user is a PI
     *
     * @return boolean true is PI, false if not
     */
    public function isPI()
    {
        return $this->getPIGroup()->exists();
    }

    public function getPIGroup()
    {
        return new UnityGroup(
            UnityGroup::ownerUID2GID($this->uid),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->REDIS,
            $this->WEBHOOK
        );
    }

    public function getOrgGroup()
    {
        return new UnityOrg(
            $this->getOrg(),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
            $this->REDIS,
            $this->WEBHOOK
        );
    }

    /**
     * Gets the groups this user is assigned to, can be more than one
     *
     * @return string[]
     */
    public function getPIGroupGIDs($ignorecache = false)
    {
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->uid, "groups");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }
        $gids = $this->LDAP->getPIGroupGIDsWithMemberUID($this->uid);
        if (!$ignorecache) {
            $this->REDIS->setCache($this->uid, "groups", $gids);
        }
        return $gids;
    }

    /**
     * Sends an email to admins about account deletion request
     * and also adds it to a table in the database
     */
    public function requestAccountDeletion()
    {
        $this->SQL->addAccountDeletionRequest($this->uid);
        $this->MAILER->sendMail(
            "admin",
            "account_deletion_request_admin",
            array(
                "user" => $this->uid,
                "name" => $this->getFullname(),
                "email" => $this->getMail()
            )
        );
    }

    /**
     * Checks if the user has requested account deletion
     *
     * @return boolean true if account deletion has been requested, false if not
     */
    public function hasRequestedAccountDeletion()
    {
        return $this->SQL->accDeletionRequestExists($this->uid);
    }

    /**
     * Checks whether a user is in a group or not
     *
     * @param  string            $uid   uid of the user
     * @param  string  or object $group group to check
     * @return boolean true if user is in group, false if not
     */
    public function isInGroup($uid, $group)
    {
        if (gettype($group) == "string") {
            $group_checked = new UnityGroup(
                $group,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->REDIS,
                $this->WEBHOOK
            );
        } else {
            $group_checked = $group;
        }

        return in_array($uid, $group_checked->getGroupMemberUIDs());
    }
}
