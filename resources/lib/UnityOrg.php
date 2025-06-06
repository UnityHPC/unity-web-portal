<?php

namespace UnityWebPortal\lib;

use Exception;

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
        $org_group = $this->getLDAPOrgGroup();

        if (!$org_group->exists()) {
            $nextGID = $this->LDAP->getNextOrgGIDNumber($this->SQL);

            $org_group->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $org_group->setAttribute("gidnumber", strval($nextGID));
            $org_group->write();
        }

        $this->REDIS->appendCacheArray("sorted_orgs", "", $this->getOrgID());
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

    public function inOrg($user, $ignorecache = false)
    {
        return in_array($user->getUID(), $this->getOrgMemberUIDs($ignorecache));
    }

    public function getOrgMembers($ignorecache = false)
    {
        $members = $this->getGroupMemberUIDs($ignorecache);
        $out = array();
        $owner_uid = $this->getOwner()->getUID();
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
            $cached_val = $this->REDIS->getCache($this->getOrgID(), "members");
            if (!is_null($cached_val)) {
                $members = $cached_val;
            }
        }
        $updatecache = false;
        if (!isset($members)) {
            $org_group = $this->getLDAPOrgGroup();
            $members = $org_group->getAttribute("memberuid");
            $updatecache = true;
        }
        if (!$ignorecache && $updatecache) {
            sort($members);
            $this->REDIS->setCache($this->getOrgID(), "members", $members);
        }
        return $members;
    }

    public function addUser($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->appendAttribute("memberuid", $user->getUID());
        $org_group->write();
        $this->REDIS->appendCacheArray($this->getOrgID(), "members", $user->getUID());
    }

    public function removeUser($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->removeAttributeEntryByValue("memberuid", $user->getUID());
        $org_group->write();
        $this->REDIS->removeCacheArray($this->getOrgID(), "members", $user->getUID());
    }
}
