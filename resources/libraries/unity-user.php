<?php

// REQUIRES config.php
// REQUIRES unity-ldap.php
// REQUIRES slurm.php
// REQUIRED unity-storage.php

/**
 * Class that represents a single user account in the Unity Cluster. This class manages ldap entries as well as slurm account manager entries.
 */
class unityUser
{
    const HOME_DIR = "/home/";  // trailing slash is important
    const HOME_QUOTA = 536870912000;

    private $uid;
    private $service_stack;

    public function __construct($uid, $service_stack)
    {
        $this->uid = $uid;

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
     * This is the method that is run when a new account is created
     *
     * @param string $firstname First name of new account
     * @param string $lastname Last name of new account
     * @param string $email email of new account
     * @param bool $isPI boolean value for if the user checked the "I am a PI box"
     * @return void
     */
    public function init($firstname, $lastname, $email)
    {
        //
        // Create LDAP group
        //
        $ldapGroupEntry = $this->getLDAPGroup();
        $id = $this->service_stack->ldap()->getUnassignedID($this->getUID());

        if (!$ldapGroupEntry->exists()) {
            $ldapGroupEntry->setAttribute("objectclass", unityLDAP::POSIX_GROUP_CLASS);
            $ldapGroupEntry->setAttribute("gidnumber", strval($id));

            if (!$ldapGroupEntry->write()) {
                throw new Exception("Failed to create POSIX group for $this->uid");
            }
        }

        //
        // Create LDAP user
        //
        $ldapUserEntry = $this->getLDAPUser();

        if (!$ldapUserEntry->exists()) {
            $ldapUserEntry->setAttribute("objectclass", unityLDAP::POSIX_ACCOUNT_CLASS);
            $ldapUserEntry->setAttribute("uid", $this->uid);
            $ldapUserEntry->setAttribute("givenname", $firstname);
            $ldapUserEntry->setAttribute("sn", $lastname);
            $ldapUserEntry->setAttribute("mail", $email);
            $ldapUserEntry->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
            $ldapUserEntry->setAttribute("loginshell", unityLDAP::DEFAULT_SHELL);
            $ldapUserEntry->setAttribute("uidnumber", strval($id));
            $ldapUserEntry->setAttribute("gidnumber", strval($id));

            if (!$ldapUserEntry->write()) {
                $ldapGroupEntry->delete();  // Cleanup previous group
                throw new Exception("Failed to create POSIX user for  $this->uid");
            }
        }

        //
        // MySQL row
        //
        if ($isPI) {
            $this->service_stack->sql()->addRequest($this->uid);
        }

        // filesystem
        $this->initFilesystem();
    }


    /**
     * Returns the ldap account entry corresponding to the user
     *
     * @return ldapEntry posix account
     */
    public function getLDAPUser()
    {
        $user_entries = $this->service_stack->ldap()->userOU->getChildren(true, "(" . unityLDAP::RDN . "=$this->uid)");

        if (count($user_entries) > 0) {
            return $user_entries[0];
        } else {
            return new ldapEntry($this->service_stack->ldap()->getConn(), unityLDAP::RDN . "=$this->uid," . unityLDAP::USERS);
        }
    }

    /**
     * Returns the ldap group entry corresponding to the user
     *
     * @return ldapEntry posix group
     */
    public function getLDAPGroup()
    {
        $group_entries = $this->service_stack->ldap()->groupOU->getChildren(true, "(" . unityLDAP::RDN . "=$this->uid)");

        if (count($group_entries) > 0) {
            return $group_entries[0];
        } else {
            return new ldapEntry($this->service_stack->ldap()->getConn(), unityLDAP::RDN . "=$this->uid," . unityLDAP::GROUPS);
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
        $result = $ldapUser->getAttribute("sshpublickey");
        if (is_null($result)) {
            return array();
        } else {
            return $result;
        }
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
        $admins = $this->service_stack->ldap()->adminGroup->getAttribute("memberuid");
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

    public function getAccount()
    {
        return new unityAccount(unityAccount::getPIUIDfromUID($this->uid), $this->service_stack);
    }

    /**
     * Gets the groups this user is assigned to, can be more than one
     * @return [type]
     */
    public function getGroups()
    {
        $groups = $this->service_stack->sacctmgr()->getAccountsFromUser($this->uid);

        $out = array();
        foreach ($groups as $group) {
            array_push($out, new unityAccount($group, $this->service_stack));
        }
        return $out;
    }

    public function initFilesystem() {
        $this->service_stack->unityfs()->createHomeDirectory($this->getUID(), self::HOME_QUOTA);
        $this->service_stack->unityfs()->createScratchDirectory($this->getUID());
        $this->service_stack->unityfs()->populateHomeDirectory($this->getUID());
        $this->service_stack->unityfs()->populateScratchDirectory($this->getUID());
    }
}
