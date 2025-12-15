<?php

namespace UnityWebPortal\lib;
use PHPOpenLDAPer\LDAPEntry;

class UnityOrg extends LDAPEntry
{
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
        parent::__construct($LDAP, $LDAP->getOrgGroupDN($this->gid));
        $gid = trim($gid);
        $this->gid = $gid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function init(): void
    {
        \ensure(!$this->exists());
        $nextGID = $this->LDAP->getNextOrgGIDNumber();
        $this->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $this->setAttribute("gidnumber", strval($nextGID));
        $this->write();
    }

    public function inOrg(UnityUser $user): bool
    {
        return in_array($user->uid, $this->getOrgMemberUIDs());
    }

    public function getOrgMembers(): array
    {
        $members = $this->getOrgMemberUIDs();
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

    public function getOrgMemberUIDs(): array
    {
        $members = $this->getAttribute("memberuid");
        sort($members);
        return $members;
    }

    public function addUser(UnityUser $user): void
    {
        $this->appendAttribute("memberuid", $user->uid);
        $this->write();
    }

    public function removeUser(UnityUser $user): void
    {
        $this->removeAttributeEntryByValue("memberuid", $user->uid);
        $this->write();
    }
}
