<?php

namespace UnityWebPortal\lib;
use PHPOpenLDAPer\LDAPEntry;

class UnityOrg
{
    public string $gid;
    private LDAPEntry $entry;
    private UnityLDAP $LDAP;
    private UnitySQL $SQL;
    private UnityMailer $MAILER;
    private UnityWebhook $WEBHOOK;
    private UnityRedis $REDIS;

    public function __construct(
        string $gid,
        UnityLDAP $LDAP,
        UnitySQL $SQL,
        UnityMailer $MAILER,
        UnityRedis $REDIS,
        UnityWebhook $WEBHOOK,
    ) {
        $gid = trim($gid);
        $this->gid = $gid;
        $this->entry = $LDAP->getOrgGroupEntry($this->gid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
        $this->REDIS = $REDIS;
    }

    public function init(): void
    {
        \ensure(!$this->entry->exists());
        $nextGID = $this->LDAP->getNextOrgGIDNumber();
        $this->entry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $this->entry->setAttribute("gidnumber", strval($nextGID));
        $this->entry->write();
        $default_value_getter = [$this->LDAP, "getSortedOrgsForRedis"];
        $this->REDIS->appendCacheArray("sorted_orgs", "", $this->gid, $default_value_getter);
    }

    public function exists(): bool
    {
        return $this->entry->exists();
    }

    public function inOrg(UnityUser $user, bool $ignorecache = false): bool
    {
        return in_array($user->uid, $this->getOrgMemberUIDs($ignorecache));
    }

    public function getOrgMembers(bool $ignorecache = false): array
    {
        $members = $this->getOrgMemberUIDs($ignorecache);
        $out = [];
        foreach ($members as $member) {
            $user_obj = new UnityUser(
                $member,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->REDIS,
                $this->WEBHOOK,
            );
            array_push($out, $user_obj);
        }
        return $out;
    }

    public function getOrgMemberUIDs(bool $ignorecache = false): array
    {
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->gid, "members");
            if (!is_null($cached_val)) {
                $members = $cached_val;
            }
        }
        $updatecache = false;
        if (!isset($members)) {
            $members = $this->entry->getAttribute("memberuid");
            $updatecache = true;
        }
        if (!$ignorecache && $updatecache) {
            sort($members);
            $this->REDIS->setCache($this->gid, "members", $members);
        }
        return $members;
    }

    public function addUser(UnityUser $user): void
    {
        $this->entry->appendAttribute("memberuid", $user->uid);
        $this->entry->write();
        $this->REDIS->appendCacheArray(
            $this->gid,
            "members",
            $user->uid,
            fn() => $this->getOrgMemberUIDs(true),
        );
    }

    public function removeUser(UnityUser $user): void
    {
        $this->entry->removeAttributeEntryByValue("memberuid", $user->uid);
        $this->entry->write();
        $this->REDIS->removeCacheArray(
            $this->gid,
            "members",
            $user->uid,
            fn() => $this->getOrgMemberUIDs(true),
        );
    }
}
