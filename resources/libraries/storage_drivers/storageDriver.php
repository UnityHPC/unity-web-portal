<?php
abstract class storageDriver {
    public const web_access_uid = 25000;

    public const HOME_FLAG = "h";
    public const SCRATCH_FLAG = "s";
    public const PROJECT_FLAG = "p";
    public const CREATE_FLAG = "c";
    public const FILESYSTEM_FLAG = "f";
    public const QUOTA_CAPABLE_FLAG = "q";
    public const EXTENDED_ACL_FLAG = "e";

    private $flags;

    public function __construct($flags)
    {
        $this->flags = $flags;
    }

    public function isHomeStorage() {
        return str_contains($this->flags, self::HOME_FLAG);
    }

    public function isScratchStorage() {
        return str_contains($this->flags, self::SCRATCH_FLAG);
    }

    public function isProjectStorage() {
        return str_contains($this->flags, self::PROJECT_FLAG);
    }

    public function canCreate() {
        return str_contains($this->flags, self::CREATE_FLAG);
    }

    public function canFilesystem() {
        return str_contains($this->flags, self::FILESYSTEM_FLAG);
    }

    public function canQuota() {
        return str_contains($this->flags, self::QUOTA_CAPABLE_FLAG);
    }

    public function canExtendedACL() {
        return str_contains($this->flags, self::EXTENDED_ACL_FLAG);
    }
    
    abstract public function createHomeDirectory($uid, $quota);
    abstract public function deleteHomeDirectory($uid);
    abstract public function createScratchDirectory($uid, $quota);
    abstract public function deleteScratchDirectory($uid);
    abstract public function createProjectDirectory($name, $owner_uid, $quota);
    abstract public function addUserToProjectDirectory($name, $uid);
    abstract public function addGroupToProjectDirectory($name, $gid);
    abstract public function deleteProjectDirectory($name);
}