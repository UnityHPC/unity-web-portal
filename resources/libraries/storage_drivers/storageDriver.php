<?php
abstract class storageDriver {
    abstract public function isHomeStorage();
    abstract public function isScratchStorage();
    abstract public function isProjectStorage();
    abstract public function createHomeDirectory($uid, $quota);
    abstract public function deleteHomeDirectory($uid);
    abstract public function createScratchDirectory($uid, $quota);
    abstract public function deleteScratchDirectory($uid);
    abstract public function createProjectDirectory($name, $owner_uid, $quota);
    abstract public function addUserToProjectDirectory($name, $uid);
    abstract public function addGroupToProjectDirectory($name, $gid);
    abstract public function deleteProjectDirectory($name);
}