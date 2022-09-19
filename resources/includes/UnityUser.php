<?php

use PHPOpenLDAPer\LDAPEntry;

/**
 * Class that represents a single user account in the Unity Cluster. This class manages ldap entries as well as slurm account manager entries.
 */
class UnityUser
{
    const HOME_DIR = "/home/";

    private $uid;

    // service stack
    private $LDAP;
    private $SQL;
    private $MAILER;

    public function __construct($uid, $LDAP, $SQL, $MAILER)
    {
        $this->uid = $uid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
    }

    public function equals($other_user)
    {
        if (!is_a($other_user, self::class)) {
            throw new Exception("Unable to check equality because the parameter is not a " . self::class . " object");
        }

        return $this->getUID() == $other_user->getUID();
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
    public function init($firstname, $lastname, $email, $org, $send_mail = true)
    {
        //
        // Create LDAP group
        //
        $ldapGroupEntry = $this->getLDAPGroup();
        $id = $this->LDAP->getUnassignedID($this->getUID());

        if (!$ldapGroupEntry->exists()) {
            $ldapGroupEntry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
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
            $ldapUserEntry->setAttribute("objectclass", UnityLDAP::POSIX_ACCOUNT_CLASS);
            $ldapUserEntry->setAttribute("uid", $this->uid);
            $ldapUserEntry->setAttribute("givenname", $firstname);
            $ldapUserEntry->setAttribute("sn", $lastname);
            $ldapUserEntry->setAttribute("mail", $email);
            $ldapUserEntry->setAttribute("o", $org);
            $ldapUserEntry->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
            $ldapUserEntry->setAttribute("loginshell", UnityLDAP::DEFAULT_SHELL);
            $ldapUserEntry->setAttribute("uidnumber", strval($id));
            $ldapUserEntry->setAttribute("gidnumber", strval($id));

            if (!$ldapUserEntry->write()) {
                $ldapGroupEntry->delete();  // Cleanup previous group
                throw new Exception("Failed to create POSIX user for  $this->uid");
            }
        }

        //
        // add to org group
        //
        $orgEntry = $this->getOrgGroup();
        // create organization if it doesn't exist
        if (!$orgEntry->exists()) {
            $orgEntry->init();
        }

        $orgEntry->addUser($this);

        //
        // send email to user
        //
        if ($send_mail) {
            $this->MAILER->sendMail(
                $this->getMail(),
                "user_created",
                array("user" => $this->uid, "org" => $this->getOrg())
            );
        }
    }


    /**
     * Returns the ldap account entry corresponding to the user
     *
     * @return ldapEntry posix account
     */
    public function getLDAPUser()
    {
        return $this->LDAP->getUserEntry($this->uid);
    }

    /**
     * Returns the ldap group entry corresponding to the user
     *
     * @return ldapEntry posix group
     */
    public function getLDAPGroup()
    {
        return $this->LDAP->getGroupEntry($this->uid);
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

    public function getOrg()
    {
        return $this->getLDAPUser()->getAttribute("o");
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

    public function getFullname()
    {
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
    public function setSSHKeys($keys, $send_mail = true)
    {
        $ldapUser = $this->getLDAPUser();
        $keys_filt = array_values(array_unique($keys));
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("sshpublickey", $keys_filt);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify SSH keys for $this->uid");
            }
        }

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
    public function setLoginShell($shell, $send_mail = true)
    {
        $ldapUser = $this->getLDAPUser();
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("loginshell", $shell);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify login shell for $this->uid");
            }
        }

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
    public function getLoginShell()
    {
        $ldapUser = $this->getLDAPUser();
        return $ldapUser->getAttribute("loginshell")[0];
    }

    /**
     * Gets the home directory of the user
     * 
     * @return string home directory of the user
     */
    public function getHomeDir()
    {
        $ldapUser = $this->getLDAPUser();
        return $ldapUser->getAttribute("homedirectory");
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
     * Checks if the current account is an admin (in the sudo group)
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
        return new UnityGroup(UnityGroup::getPIUIDfromUID($this->uid), $this->LDAP, $this->SQL, $this->MAILER);
    }

    public function getOrgGroup()
    {
        return new UnityOrg($this->getOrg(), $this->LDAP, $this->SQL, $this->MAILER);
    }

    /**
     * Gets the groups this user is assigned to, can be more than one
     * @return [type]
     */
    public function getGroups()
    {
        $all_pi_groups = $this->LDAP->getAllPIGroups($this->SQL, $this->MAILER);

        $out = array();
        foreach ($all_pi_groups as $pi_group) {
            if (in_array($this->getUID(), $pi_group->getGroupMemberUIDs())) {
                array_push($out, $pi_group);
            }
        }

        return $out;
    }
}
