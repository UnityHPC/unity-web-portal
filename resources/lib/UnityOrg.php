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

    public function __construct($orgid, $LDAP, $SQL, $MAILER, $REDIS)
    {
        $this->orgid = $orgid;

        $this->LDAP = $LDAP;
        $this->SQL = $SQL;
        $this->MAILER = $MAILER;
        $this->REDIS = $REDIS;
    }

    public function init()
    {
        $org_group = $this->getLDAPOrgGroup();

        if (!$org_group->exists()) {
            $nextGID = $this->LDAP->getNextOrgGIDNumber();

            $org_group->setAttribute("objectclass", UnityLDAP::POSIX_GROUP_CLASS);
            $org_group->setAttribute("gidnumber", strval($nextGID));

            if (!$org_group->write()) {
                throw new Exception("Failed to create POSIX group for " . $this->orgid);  // this shouldn't execute
            }
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

    public function inOrg($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $members = $org_group->getAttribute("memberuid");
        return in_array($user, $members);
    }

    public function getOrgMembers($ignorecache = false)
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

        $out = array();
        $cache_arr = array();
        foreach ($members as $member) {
            $user_obj = new UnityUser($member, $this->LDAP, $this->SQL, $this->MAILER, $this->REDIS);
            array_push($out, $user_obj);
            array_push($cache_arr, $user_obj->getUID());
        }

        if (!$ignorecache && $updatecache) {
            sort($cache_arr);
            $this->REDIS->setCache($this->getOrgID(), "members", $cache_arr);
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

        $this->REDIS->appendCacheArray($this->getOrgID(), "members", $user->getUID());
    }

    public function removeUser($user)
    {
        $org_group = $this->getLDAPOrgGroup();
        $org_group->removeAttributeEntryByValue("memberuid", $user->getUID());

        if (!$org_group->write()) {
            throw new Exception("Unable to write to org group");
        }

        $this->REDIS->removeCacheArray($this->getOrgID(), "members", $user->getUID());
    }
}
