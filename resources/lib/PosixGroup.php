<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPEntry;

/*
does not extend LDAPEntry because UnityGroup extends this and I don't want UnityGroup
to extend LDAPEntry because the functions from LDAPEntry should not be exposed there
*/
class PosixGroup
{
    protected LDAPEntry $entry;

    public function __construct(LDAPEntry $entry)
    {
        $this->entry = $entry;
    }

    public function getDN(): string
    {
        return $this->entry->getDN();
    }

    public function equals(PosixGroup $other_group): bool
    {
        return $this->getDN() == $other_group->getDN();
    }

    public function exists(): bool
    {
        return $this->entry->exists();
    }

    /** @return string[] */
    public function getMemberUIDs(): array
    {
        $members = $this->entry->getAttribute("memberuid");
        sort($members);
        return $members;
    }

    public function addMemberUID(string $uid): void
    {
        $this->entry->appendAttribute("memberuid", $uid);
    }

    /** @param string[] $uids */
    public function addMemberUIDs(array $uids): void
    {
        foreach ($uids as $uid) {
            $this->entry->appendAttribute("memberuid", $uid);
        }
    }

    public function removeMemberUID(string $uid): void
    {
        $this->entry->removeAttributeEntryByValue("memberuid", $uid);
    }

    /** @param string[] $uids */
    public function removeMemberUIDs(array $uids): void
    {
        foreach ($uids as $uid) {
            $this->entry->removeAttributeEntryByValue("memberuid", $uid);
        }
    }

    public function memberUIDExists(string $uid): bool
    {
        return in_array($uid, $this->getMemberUIDs());
    }

    /** @param string[] $uids */
    public function overwriteMemberUIDs(array $uids): void
    {
        $this->entry->setAttribute("memberuid", $uids);
    }
}
