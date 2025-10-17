<?php

namespace UnityWebPortal\lib;

use Exception;

class UnityOrg
{
    public $gid;
    private $entry;

    private $MAILER;
    private $SQL;
    private $LDAP;
    private $REDIS;
    private $WEBHOOK;

    public function __construct($gid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK)
    {
        $gid = trim($gid);
        $this->gid = $gid;
        $this->entry = $LDAP->getOrgGroupEntry($this->gid);

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
        $this->REDIS = $REDIS;
    }

    public function init()
    {
        \ensure(!$this->entry->exists());
        $nextGID = $this->LDAP->getNextOrgGIDNumber($this->SQL);
        $this->entry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
        $this->entry->setAttribute("gidnumber", strval($nextGID));
        $this->entry->write();
        $this->REDIS->appendCacheArray("sorted_orgs", "", $this->gid);
    }

    public function exists()
    {
        return $this->entry->exists();
    }

    public function inOrg($user, $ignorecache = false)
    {
        return in_array($user->uid, $this->getOrgMemberUIDs($ignorecache));
    }

    public function getOrgMembers($ignorecache = false)
    {
        $members = $this->getOrgMemberUIDs($ignorecache);
        $out = array();
        foreach ($members as $member) {
            $user_obj = new UnityUser(
                $member,
                $this->LDAP,
                $this->SQL,
                $this->MAILER,
                $this->REDIS,
                $this->WEBHOOK
            );
            array_push($out, $user_obj);
        }
        return $out;
    }

    public function getOrgMemberUIDs($ignorecache = false)
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

    public function addUser($user)
    {
        $this->entry->appendAttribute("memberuid", $user->uid);
        $this->entry->write();
        $this->REDIS->appendCacheArray($this->gid, "members", $user->uid);
    }

    public function removeUser($user)
    {
        $this->entry->removeAttributeEntryByValue("memberuid", $user->uid);
        $this->entry->write();
        $this->REDIS->removeCacheArray($this->gid, "members", $user->uid);
    }
}
