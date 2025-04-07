<?php

namespace UnityWebPortal\lib;

use Exception;
use PHPOpenLDAPer\LdapEntry;

class UnityOrg
{
    private $orgid;

    private $MAILER;
    private $SQL;
    private $LDAP;
    private $REDIS;
    private $WEBHOOK;

    public function __construct($orgid, $LDAP, $SQL, $MAILER, $REDIS, $WEBHOOK)
    {
        $this->orgid = $orgid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->WEBHOOK = $WEBHOOK;
        $this->REDIS = $REDIS;
    }

    public function init()
    {
        $entry = $this->getLDAPEntry();

        if (!$entry->exists()) {
            $nextGID = $this->LDAP->getNextOrgGIDNumber($this->SQL);

            $entry->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $entry->setAttribute("gidnumber", strval($nextGID));

            if (!$entry->write()) {
                throw new Exception("Failed to create POSIX group for " . $this->orgid);  // this shouldn't execute
            }
        }

        $this->REDIS->appendCacheArray("sorted_orgs", "", $this->getOrgID());
    }

    public function exists()
    {
        return $this->getLDAPEntry()->exists();
    }

    public function getLDAPEntry(): LdapEntry
    {
        return $this->LDAP->getOrgGroupEntry($this->orgid);
    }

    public function getOrgID()
    {
        return $this->orgid;
    }

    public function userExists(UnityUser $user, $ignorecache = false): bool
    {
        $members = $this->getOrgMemberUIDs($ignorecache);
        return in_array($user->getUID(), $members);
    }

    public function getOrgMemberUIDs($ignorecache = false): array
    {
        if (!$ignorecache) {
            $cached_val = $this->REDIS->getCache($this->getOrgID(), "members");
            if (!is_null($cached_val)) {
                return $cached_val;
            }
        }
        $entry = $this->getLDAPEntry();
        $members = $entry->getAttribute("memberuid");
        $members = (is_null($members) ? [] : $members);
        sort($members);
        $this->REDIS->setCache($this->getOrgID(), "members", $members);
        return $members;
    }

    public function getOrgMembers($ignorecache = false)
    {
        $memberuids = $this->getOrgMemberUIDs($ignorecache);
        $out = array();
        foreach ($memberuids as $uid) {
            $user_obj = new UnityUser($uid, $this->LDAP, $this->SQL, $this->MAILER, $this->REDIS, $this->WEBHOOK);
            array_push($out, $user_obj);
        }
        return $out;
    }

    public function addUser($user)
    {
        $entry = $this->getLDAPEntry();
        $entry->appendAttribute("memberuid", $user->getUID());

        if (!$entry->write()) {
            throw new Exception("Unable to write to org group");
        }

        $this->REDIS->appendCacheArray($this->getOrgID(), "members", $user->getUID());
    }

    public function removeUser($user)
    {
        $entry = $this->getLDAPEntry();
        $entry->removeAttributeEntryByValue("memberuid", $user->getUID());

        if (!$entry->write()) {
            throw new Exception("Unable to write to org group");
        }

        $this->REDIS->removeCacheArray($this->getOrgID(), "members", $user->getUID());
    }
}
