<?php

namespace UnityWebPortal\lib;

use Exception;

class UnityOrg
{
    private $orgid;

    private $MAILER;
    private $SQL;
    private $LDAP;
    private $WEBHOOK;

    public function __construct($orgid, $LDAP, $SQL, $MAILER, $WEBHOOK)
    {
        $this->orgid = $orgid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function init()
    {
        $org_group = $this->getLDAPOrgGroup();

        if (!$org_group->exists()) {
            $nextGID = $this->LDAP->getNextOrgGIDNumber($this->SQL);

            $org_group->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $org_group->setAttribute("gidnumber", strval($nextGID));

            if (!$org_group->write()) {
                throw new Exception("Failed to create POSIX group for " . $this->orgid);  // this shouldn't execute
            }
        }
    }

    public function exists()
    {
        return $this->getLDAPOrgGroup()->exists();
    }

    public function getLDAPOrgGroup()
    {
        return $this->LDAP->getOrgGroupEntry($this->orgid);
    }

    public function getOrgID()
    {
        return $this->orgid;
    }

    public function inOrg($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $members = $org_group->getAttribute("memberuid");
        return in_array($user, $members);
    }

    public function getOrgMembers()
    {
        if (!isset($members)) {
            $org_group = $this->getLDAPOrgGroup();
            $members = $org_group->getAttribute("memberuid");
        }

        $out = array();
        foreach ($members as $member) {
            $user_obj = new UnityUser($member, $this->LDAP, $this->SQL, $this->MAILER, $this->WEBHOOK);
            array_push($out, $user_obj);
        }
        return $out;
    }

    public function addUser($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->appendAttribute("memberuid", $user->getUID());
        if (!$org_group->write()) {
            throw new Exception("Unable to write to org group");
        }
    }

    public function removeUser($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->removeAttributeEntryByValue("memberuid", $user->getUID());
        if (!$org_group->write()) {
            throw new Exception("Unable to write to org group");
        }
    }
}
