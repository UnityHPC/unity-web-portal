<?php

namespace UnityWebPortal\lib;
use PHPOpenLDAPer\LDAPEntry;
use PHPOpenLDAPer\PosixGroup;

class UnityOrg extends PosixGroup
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
        $gid = trim($gid);
        $this->gid = $gid;
        $this->entry = $LDAP->getOrgGroupEntry($this->gid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
    }

    public function init(): void
    {
        \ensure(!$this->entry->exists());
        $nextGID = $this->LDAP->getNextOrgGIDNumber();
        $this->entry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $this->entry->setAttribute("gidnumber", strval($nextGID));
        $this->entry->write();
    }

    public function getOrgMembers(): array
    {
        $members = $this->getMemberUIDs();
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
}
