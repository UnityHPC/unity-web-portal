<?php

// REQUIRES config.php
// REQUIRES unity-ldap.php
// REQUIRES slurm.php

/**
 * Class that represents a single user account in the Unity Cluster. This class manages ldap entries as well as slurm account manager entries.
 */
class unityUser
{
    public function clone($uid)
    {
        return new unityUser($uid, $this->ldap, $this->sql, $this->sacctmgr);
    }

    const HOME_DIR = "/home/";  // trailing slash is important

    private $uid;

    // Services
    private $ldap;
    private $sql;
    private $sacctmgr;

    public function __construct($uid, $unityLDAP, $unitySQL, $sacctmgr)
    {
        $this->uid = $uid;

        $this->ldap = $unityLDAP;  // Set LDAP connection instance var
        $this->sql = $unitySQL;  // Set SQL connection instance var
        $this->sacctmgr = $sacctmgr;  // Set sacctmgr instance var
    }

    /**
     * This is the method that is run when a new account is created
     *
     * @param string $firstname First name of new account
     * @param string $lastname Last name of new account
     * @param string $email email of new account
     * @param bool $isPI boolean value for if the user checked the "I am a PI box"
     * @return void
     */
    public function init($firstname, $lastname, $email, $isPI)
    {
        //
        // Create LDAP group
        //
        $ldapGroupEntry = $this->getLDAPGroup();

        if (!$ldapGroupEntry->exists()) {
            $nextGID = $this->ldap->getNextGID();

            $ldapGroupEntry->setAttribute("objectclass", unityLDAP::POSIX_GROUP_CLASS);
            $ldapGroupEntry->setAttribute("gidnumber", strval($nextGID));

            if (!$ldapGroupEntry->write()) {
                throw new Exception("Failed to create POSIX group for $this->uid");
            }
        }

        //
        // Create LDAP user
        //
        $ldapUserEntry = $this->getLDAPUser();

        if (!$ldapUserEntry->exists()) {
            $nextUID = $this->ldap->getNextUID();
            $currentGID = $ldapGroupEntry->getAttribute("gidnumber")[0];
            if ($currentGID != $nextUID) {
                $ldapGroupEntry->delete();  // Cleanup previous group
                throw new Exception("UID/GID mismatch: Attempting to match UID $nextUID with GID $currentGID");
            }

            $ldapUserEntry->setAttribute("objectclass", unityLDAP::POSIX_ACCOUNT_CLASS);
            $ldapUserEntry->setAttribute("uid", $this->uid);
            $ldapUserEntry->setAttribute("givenname", $firstname);
            $ldapUserEntry->setAttribute("sn", $lastname);
            $ldapUserEntry->setAttribute("mail", $email);
            $ldapUserEntry->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
            $ldapUserEntry->setAttribute("loginshell", unityLDAP::NOLOGIN_SHELL);
            $ldapUserEntry->setAttribute("uidnumber", strval($nextUID));
            $ldapUserEntry->setAttribute("gidnumber", strval($currentGID));

            if (!$ldapUserEntry->write()) {
                $ldapGroupEntry->delete();  // Cleanup previous group
                throw new Exception("Failed to create POSIX user for  $this->uid");
            }
        }

        //
        // MySQL row
        //
        if ($isPI) {
            $this->sql->addRequest($this->uid);
        }
    }


    /**
     * Returns the ldap account entry corresponding to the user
     *
     * @return ldapEntry posix account
     */
    public function getLDAPUser()
    {
        $user_entries = $this->ldap->userOU->getChildren(true, "(" . unityLDAP::RDN . "=$this->uid)");

        if (count($user_entries) > 0) {
            return $user_entries[0];
        } else {
            return new ldapEntry($this->ldap->getConn(), unityLDAP::RDN . "=$this->uid," . unityLDAP::USERS);
        }
    }

    /**
     * Returns the ldap group entry corresponding to the user
     *
     * @return ldapEntry posix group
     */
    public function getLDAPGroup()
    {
        $group_entries = $this->ldap->groupOU->getChildren(true, "(" . unityLDAP::RDN . "=$this->uid)");

        if (count($group_entries) > 0) {
            return $group_entries[0];
        } else {
            return new ldapEntry($this->ldap->getConn(), unityLDAP::RDN . "=$this->uid," . unityLDAP::GROUPS);
        }
    }

    public function exists()
    {
        return $this->getLDAPUser()->exists() && $this->getLDAPGroup()->exists();
    }

    //
    // User Attribute Functions
    //

    /**
     * Get method for NetID
     *
     * @return string Net ID of user
     */
    public function getUID()
    {
        return $this->uid;
    }

    /**
     * Sets the firstname of the account and the corresponding ldap entry if it exists
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->getLDAPUser()->setAttribute("givenname", $firstname);

        if (!$this->getLDAPUser()->write()) {
            throw new Exception("Error updating LDAP entry $this->uid");
        }
    }

    /**
     * Gets the firstname of the account
     *
     * @return string firstname
     */
    public function getFirstname()
    {
        return $this->getLDAPUser()->getAttribute("givenname")[0];
    }

    /**
     * Sets the lastname of the account and the corresponding ldap entry if it exists
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->getLDAPUser()->setAttribute("sn", $lastname);

        if (!$this->getLDAPUser()->write()) {
            throw new Exception("Error updating LDAP entry $this->uid");
        }
    }

    /**
     * Get method for the lastname on the account
     *
     * @return string lastname
     */
    public function getLastname()
    {
        return $this->getLDAPUser()->getAttribute("sn")[0];
    }

    public function getFullname() {
        return $this->getFirstname() . " " . $this->getLastname();
    }

    /**
     * Sets the mail in the account and the ldap entry
     *
     * @param string $mail
     */
    public function setMail($email)
    {
        $this->getLDAPUser()->setAttribute("mail", $email);

        if (!$this->getLDAPUser()->write()) {
            throw new Exception("Error updating LDAP entry $this->uid");
        }
    }

    /**
     * Method to get the mail instance var
     *
     * @return string email address
     */
    public function getMail()
    {
        return $this->getLDAPUser()->getAttribute("mail")[0];
    }

    /**
     * Sets the SSH keys on the account and the corresponding entry
     *
     * @param array $keys String array of openssh-style ssh public keys
     */
    public function setSSHKeys($keys)
    {
        $ldapUser = $this->getLDAPUser();
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("sshpublickey", $keys);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify SSH keys for $this->uid");
            }
        }
    }

    /**
     * Returns the SSH keys attached to the account
     *
     * @return array String array of ssh keys
     */
    public function getSSHKeys()
    {
        $ldapUser = $this->getLDAPUser();
        return $ldapUser->getAttribute("sshpublickey");
    }

    /**
     * Sets the login shell for the account
     *
     * @param string $shell absolute path to shell
     */
    public function setLoginShell($shell)
    {
        $ldapUser = $this->getLDAPUser();
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("loginshell", $shell);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify login shell for $this->uid");
            }
        }
    }

    /**
     * Gets the login shell of the account
     *
     * @return string absolute path to login shell
     */
    public function getLoginShell()
    {
        $ldapUser = $this->getLDAPUser();
        return $ldapUser->getAttribute("loginshell")[0];
    }

    public function setHomeDir($home)
    {
        $ldapUser = $this->getLDAPUser();
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("homedirectory", $home);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify home directory for $this->uid");
            }
        }
    }

    /**
     * Gets the home directory of the user, the home directory is immutable
     *
     * @return string path to home directory
     */
    public function getHomeDir()
    {
        return self::HOME_DIR . $this->netid;
    }

    /**
     * Checks if the current account is an admin (in the sudo group)
     *
     * @return boolean true if admin, false if not
     */
    public function isAdmin()
    {
        $admins = $this->ldap->adminGroup->getAttribute("memberuid");
        return in_array($this->uid, $admins);
    }

    /**
     * Checks if current user is a PI
     *
     * @return boolean true is PI, false if not
     */
    public function isPI()
    {
        return $this->getAccount()->exists();
    }

    /**
     * Activates the account for normal login
     *
     * @return void
     */
    public function activate()
    {
        $this->setLoginShell(unityLDAP::DEFAULT_SHELL);
    }

    /**
     * Deactivates the account (user leaves, is forced to leave)
     *
     * @return void
     */
    public function deactivate()
    {
        $this->setLoginShell(unityLDAP::NOLOGIN_SHELL);
    }

    public function isActive()
    {
        return $this->getLoginShell() != unityLDAP::NOLOGIN_SHELL;
    }

    public function getAccount()
    {
        return new unityAccount(unityAccount::getPIUIDfromUID($this->uid), $this->ldap, $this->sql, $this->sacctmgr);
    }

    /**
     * Gets the groups this user is assigned to, can be more than one
     * @return [type]
     */
    public function getGroups()
    {
        $groups = $this->sacctmgr->getAccountsFromUser($this->uid);

        $out = array();
        foreach ($groups as $group) {
            array_push($out, new unityAccount($group, $this->ldap, $this->sql, $this->sacctmgr));
        }
        return $out;
    }

    public function getGroup($pi_uid) {
        return new unityAccount($pi_uid, $this->ldap, $this->sql, $this->sacctmgr);
    }
}
