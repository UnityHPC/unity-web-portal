<?php

class truenasCoreStorageDriver extends storageDriver
{
    private $curl;  // curl object
    private $url;
    private $home_dataset;
    private $scratch_dataset;
    private $project_dataset;

    public function __construct($url, $api_key, $home_dataset = NULL, $scratch_dataset = NULL, $project_dataset = NULL)
    {
        $this->url = $url;
        $this->home_dataset = $home_dataset;
        $this->scratch_dataset = $scratch_dataset;
        $this->project_dataset = $project_dataset;

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);  // comment for debug
    }

    public function isHomeStorage()
    {
        // in the future we should add a verification step that checks to make sure the dataset exists and is writable
        return $this->home_dataset != NULL;
    }

    public function isScratchStorage()
    {
        return $this->scratch_dataset != NULL;
    }

    public function isProjectStorage()
    {
        return $this->project_dataset != NULL;
    }

    public function createHomeDirectory($user, $quota)
    {
        if (!$this->isHomeStorage()) {
            throw new Exception("This storage device has not been configured to support home directories");
        }

        $dataset = $this->home_dataset . $user;

        // CREATE DATASET;
        $path = $this->url . "/pool/dataset";
        $data = <<<DATA
        {
            "name": "$dataset",
            "quota": $quota
        }
        DATA;

        curl_setopt($this->curl, CURLOPT_POST, 1);  // this is a post request
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);  // send data

        curl_setopt($this->curl, CURLOPT_URL, $path);

        curl_exec($this->curl);
        if (curl_getinfo($this->curl)["http_code"] != 200) {
            throw new Exception("Unable to create dataset for home directory");
        }

        // SET DATASET PERMS
        $path = $this->url . "/pool/dataset/id/" . str_replace('/', '%2F', $dataset) . "/permission";
        $data = <<<DATA
        {
            "user": "$user",
            "group": "$user",
            "acl": [
                {
                    "tag": "owner@",
                    "id": null,
                    "type": "ALLOW",
                    "perms": {"BASIC": "FULL_CONTROL"},
                    "flags": {"BASIC": "INHERIT"}
                },
                {
                    "tag": "USER",
                    "id": "$this->write_uid",
                    "type": "ALLOW",
                    "perms": {"BASIC": "FULL_CONTROL"},
                    "flags": {"BASIC": "INHERIT"}
                }
            ],
            "options": {
                "stripacl": false,
                "recursive": true,
                "traverse": true
            }
        }
        DATA;
        
        curl_setopt($this->curl, CURLOPT_POST, 1);  // this is a post request
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);  // send data

        curl_setopt($this->curl, CURLOPT_URL, $path);
        curl_exec($this->curl);

        if (curl_getinfo($this->curl)["http_code"] != 200) {
            $this->deleteHomeDirectory($user);
            throw new Exception("Unable to create dataset for home directory");
        }

        curl_close($this->curl);  // close session
    }

    public function deleteHomeDirectory($user)
    {
        $dataset = $this->home_dataset . $user;

        $path = $this->host . "/pool/dataset/id/" . str_replace('/', '%2F', $dataset);
        $data = <<<DATA
        {
            "recursive": true,
            "force": true
        }
        DATA;

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);  // send data

        curl_setopt($this->curl, CURLOPT_URL, $path);
        curl_exec($this->curl);

        if (curl_getinfo($this->curl)["http_code"] != 200) {
            throw new Exception("Unable to delete dataset");
        }

        curl_close($this->curl);
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

    /*
    private function populateHomeDirectory($user) {
        $source_dir = config::DOCS_ROOT . "/etc/skel/home";
        $source_dir_obj = new FilesystemIterator($source_dir);

        $dest_dir = config::STORAGE["home_location"] . "/" . $user;

        foreach ($source_dir_obj as $file) {
            $filename = $file->getFilename();
            $destination = $dest_dir . "/" . $filename;
            copy($source_dir . "/" . $filename, $destination);
            chown($destination, $user);
            chgrp($destination, $user);
        }

        //create scratch space
        $scratch_web_location = config::STORAGE["scratch_web_mount"] . "/" . $user;
        mkdir($scratch_web_location, 0700, true);
        $this->populateScratchDirectory($user);

        // set directory perms after populating it (then the website loses access!) TODO in the future we should use posix ACLs on the scratch space as well so the website has permanent access, but we don't have any features that can take care of that right now
        chgrp($scratch_web_location, $user);
        chown($scratch_web_location, $user);

        // create scratch space sym link
        symlink(config::STORAGE["scratch_mount"] . "/" . $user, $dest_dir . "/scratch");
    }*/

    /*
    private function populateScratchDirectory($user) {
        $source_dir = config::DOCS_ROOT . "/etc/skel/scratch";
        $source_dir_obj = new FilesystemIterator($source_dir);

        $dest_dir = config::STORAGE["scratch_web_mount"] . "/" . $user;

        foreach ($source_dir_obj as $file) {
            $filename = $file->getFilename();
            $destination = $dest_dir . "/" . $filename;
            copy($source_dir . "/" . $filename, $destination);
            chown($destination, $user);
            chgrp($destination, $user);
        }
    }*/
}
