<?php

namespace UnityWebPortal\lib;

use PHPOpenLDAPer\LDAPEntry;
use \Exception;

class PosixGroup
{
    private LDAPEntry $entry;
    private string $gid;

    public function __construct(LDAPEntry $entry, string $gid)
    {
        $this->gid = $gid;
        $this->entry = $entry;
    }

    public function getDN(): string
    {
        return $this->entry->getDN();
    }

    public function equals(PosixGroup $other_group): bool
    {
        if (!is_a($other_group, self::class)) {
            throw new Exception(
                "Unable to check equality because the parameter is not a " .
                    self::class .
                    " object",
            );
        }
        return $this->getDN() == $other_group->getDN();
    }

    public function __toString(): string
    {
        return $this->gid;
    }

    public function exists(): bool
    {
        return $this->entry->exists();
    }

    public function getMemberUIDs(): array
    {
        $members = $this->entry->getAttribute("memberuid");
        sort($members);
        return $members;
    }

    public function addMemberUID(string $uid): void
    {
        $this->entry->appendAttribute("memberuid", $uid);
        $this->entry->write();
    }

    public function removeMemberUID(string $uid): void
    {
        $this->entry->removeAttributeEntryByValue("memberuid", $uid);
        $this->entry->write();
    }

    public function mermberUIDExists(string $uid): bool
    {
        return in_array($uid, $this->getMemberUIDs());
    }
}
