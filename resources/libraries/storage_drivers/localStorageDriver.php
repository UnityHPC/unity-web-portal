<?php

class localStorageDriver extends storageDriver
{
    private $path;
    private $home_location;
    private $scratch_location;
    private $project_location;

    public function __construct($path, $home_location = NULL, $scratch_location = NULL, $project_location = NULL)
    {
        $this->path = $path;
        $this->home_location = $home_location;
        $this->scratch_location = $scratch_location;
        $this->project_location = $project_location;
    }

    public function isHomeStorage()
    {
        // in the future check that directory exists and is writable
        return $this->home_location != NULL;
    }

    public function isScratchStorage()
    {
        return $this->scratch_location != NULL;
    }

    public function isProjectStorage()
    {
        return $this->project_location != NULL;
    }

    public function createHomeDirectory($user, $quota)
    {
        throw new Exception("Not yet implemented");
    }

    public function deleteHomeDirectory($user)
    {
        throw new Exception("Not yet implemented");
    }

    public function createScratchDirectory($uid, $quota)
    {
        throw new Exception("Not yet implemented");
    }

    public function deleteScratchDirectory($uid)
    {
        throw new Exception("Not yet implemented");
    }

    public function createProjectDirectory($name, $owner_uid, $quota)
    {
        throw new Exception("Not yet implemented");
    }

    public function addUserToProjectDirectory($name, $uid)
    {
        throw new Exception("Not yet implemented");
    }

    public function addGroupToProjectDirectory($name, $gid)
    {
        throw new Exception("Not yet implemented");
    }

    public function deleteProjectDirectory($name)
    {
        throw new Exception("Not yet implemented");
    }
}
