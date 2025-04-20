<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPEntry;
use Exception;

class UnityUser
{
    private const HOME_DIR = "/home/";

    private $uid;

    // service stack
    private $LDAP;
    private $SQL;
    private $MAILER;
    private $WEBHOOK;

    public function __construct($uid, $LDAP, $SQL, $MAILER, $WEBHOOK)
    {
        $this->uid = $uid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
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
    public function init($send_mail = true)
    {
        //
        // Create LDAP group
        //
        $ldapGroupEntry = $this->getLDAPGroup();
        $id = $this->LDAP->getUnassignedID($this->getUID(), $this->SQL);

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
            $ldapUserEntry->setAttribute("givenname", $this->getFirstname());
            $ldapUserEntry->setAttribute("sn", $this->getLastname());
            $ldapUserEntry->setAttribute("mail", $this->getMail());
            $ldapUserEntry->setAttribute("o", $this->getOrg());
            $ldapUserEntry->setAttribute("homedirectory", self::HOME_DIR . $this->uid);
            $ldapUserEntry->setAttribute("loginshell", $this->LDAP->getDefUserShell());
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

        if (!$orgEntry->inOrg($this->uid)) {
            $orgEntry->addUser($this);
        }

        //
        // add to audit log
        //
        $this->SQL->addLog(
            $this->getUID(),
            $_SERVER['REMOTE_ADDR'],
            "user_added",
            $this->getUID()
        );

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

    public function setOrg($org)
    {
        $ldap_user = $this->getLDAPUser();
        $ldap_user->setAttribute("o", $org);

        if (!$ldap_user->write()) {
            throw new Exception("Error updating LDAP entry $this->uid");
        }
    }

    public function getOrg()
    {
        if ($this->exists()) {
            $org = $this->getLDAPUser()->getAttribute("o")[0];
            return $this->getLDAPUser()->getAttribute("o")[0];
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
        $ldap_user = $this->getLDAPUser();
        $ldap_user->setAttribute("givenname", $firstname);
        $operator = is_null($operator) ? $this->getUID() : $operator->getUID();

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "firstname_changed",
            $this->getUID()
        );

        if (!$ldap_user->write()) {
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
        if ($this->exists()) {
            $firstname = $this->getLDAPUser()->getAttribute("givenname")[0];
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
        $ldap_user = $this->getLDAPUser();
        $ldap_user->setAttribute("sn", $lastname);
        $operator = is_null($operator) ? $this->getUID() : $operator->getUID();

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "lastname_changed",
            $this->getUID()
        );

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
        if ($this->exists()) {
            $lastname = $this->getLDAPUser()->getAttribute("sn")[0];
            return $lastname;
        }

        return null;
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
    public function setMail($email, $operator = null)
    {
        $ldap_user = $this->getLDAPUser();
        $ldap_user->setAttribute("mail", $email);
        $operator = is_null($operator) ? $this->getUID() : $operator->getUID();

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "email_changed",
            $this->getUID()
        );

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
        if ($this->exists()) {
            $mail = $this->getLDAPUser()->getAttribute("mail")[0];
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
        $ldapUser = $this->getLDAPUser();
        $operator = is_null($operator) ? $this->getUID() : $operator->getUID();
        $keys_filt = array_values(array_unique($keys));
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("sshpublickey", $keys_filt);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify SSH keys for $this->uid");
            }
        }

        //
        // add audit log
        //
        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "sshkey_modify",
            $this->getUID()
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
    public function getSSHKeys()
    {
        if ($this->exists()) {
            $ldapUser = $this->getLDAPUser();
            $result = $ldapUser->getAttribute("sshpublickey");
            if (is_null($result)) {
                $keys = array();
            } else {
                $keys = $result;
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
        $ldapUser = $this->getLDAPUser();
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("loginshell", $shell);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify login shell for $this->uid");
            }
        }

        $operator = is_null($operator) ? $this->getUID() : $operator->getUID();

        $this->SQL->addLog(
            $operator,
            $_SERVER['REMOTE_ADDR'],
            "loginshell_changed",
            $this->getUID()
        );
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
        if ($this->exists()) {
            $ldapUser = $this->getLDAPUser();

            $loginshell = $ldapUser->getAttribute("loginshell")[0];
            return $loginshell;
        }

        return null;
    }

    public function setHomeDir($home, $operator = null)
    {
        $ldapUser = $this->getLDAPUser();
        if ($ldapUser->exists()) {
            $ldapUser->setAttribute("homedirectory", $home);
            if (!$ldapUser->write()) {
                throw new Exception("Failed to modify home directory for $this->uid");
            }

            $operator = is_null($operator) ? $this->getUID() : $operator->getUID();

            $this->SQL->addLog(
                $operator,
                $_SERVER['REMOTE_ADDR'],
                "homedir_changed",
                $this->getUID()
            );
        }
    }

    /**
     * Gets the home directory of the user
     *
     * @return string home directory of the user
     */
    public function getHomeDir()
    {
        if ($this->exists()) {
            $ldapUser = $this->getLDAPUser();
            $homedir = $ldapUser->getAttribute("homedirectory");
            return $homedir;
        }
        return null;
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
        return new UnityGroup(
            UnityGroup::getPIUIDfromUID($this->uid),
            $this->LDAP,
            $this->SQL,
            $this->MAILER,
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
            $this->WEBHOOK
        );
    }

    /**
     * Gets the groups this user is assigned to, can be more than one
     * @return [type]
     */
    public function getGroups()
    {
        $out = array();
        $all_pi_groups = $this->LDAP->getAllPIGroups($this->SQL, $this->MAILER, $this->WEBHOOK);
        foreach ($all_pi_groups as $pi_group) {
            if (in_array($this->getUID(), $pi_group->getGroupMemberUIDs())) {
                array_push($out, $pi_group);
            }
        }
        return $out;
    }

    /**
     * Sends an email to admins about account deletion request and also adds it to a table in the database
     */
    public function requestAccountDeletion()
    {
        $this->SQL->addAccountDeletionRequest($this->getUID());
        $this->MAILER->sendMail(
            "admin",
            "account_deletion_request_admin",
            array(
                "user" => $this->getUID(),
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
        return $this->SQL->accDeletionRequestExists($this->getUID());
    }

    /**
     * Checks whether a user is in a group or not
     * @param  string  $uid   uid of the user
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
                $this->WEBHOOK
            );
        } else {
            $group_checked = $group;
        }

        return in_array($uid, $group_checked->getGroupMemberUIDs());
    }
}
