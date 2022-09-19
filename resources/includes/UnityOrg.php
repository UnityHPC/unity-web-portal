<?php

class UnityOrg {
    private $orgid;

    private $MAILER;
    private $SQL;
    private $LDAP;

    public function __construct($orgid, $LDAP, $SQL, $MAILER)
    {
        $this->orgid = $orgid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
    }

    public function init() {
        $org_group = $this->getLDAPOrgGroup();

        if (!$org_group->exists()) {
            $nextGID = $this->LDAP->getNextOrgGIDNumber();

            $org_group->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $org_group->setAttribute("gidnumber", strval($nextGID));

            if (!$org_group->write()) {
                throw new Exception("Failed to create POSIX group for " . $this->orgid);  // this shouldn't execute
            }
        }
    }

    public function exists() {
        return $this->getLDAPOrgGroup()->exists();
    }

    public function getLDAPOrgGroup()
    {
        return $this->LDAP->getOrgGroupEntry($this->orgid);
    }

    public function inOrg($user) {
        $org_group = $this->getLDAPOrgGroup();
        $members = $org_group->getAttribute("memberuid");
        return in_array($user, $members);
    }

    public function addUser($user) {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->appendAttribute("memberuid", $user->getUID());

        if (!$org_group->write()) {
            throw new Exception("Unable to write to org group");
        }
    }

    public function removeUser($user) {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->removeAttributeEntryByValue("memberuid", $user->getUID());

        if (!$org_group->write()) {
            throw new Exception("Unable to write to org group");
        }
    }
}