<?php

class unityStorage
{
    public function __construct()
    {
        
    }

    private static function getTrueNASCurlObject($api_key) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
        //curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // uncomment for debug

        return $curl;
    }

    private function getVASTCurlObject() {

    }

    public function createHomeDirectory($user)
    {
        // This method should be redone for different deployments

        $storage_device = "nas1";
        $CONF = config::STORAGE[$storage_device];
        $curl = unityStorage::getTrueNASCurlObject($CONF["key"]);

        $dataset = $CONF["home_dataset"] . $user;

        // CREATE DATASET
        $home_quota = config::STORAGE["home_quota"];
        $path = $CONF["host"] . "/pool/dataset";
        $data = <<<DATA
        {
            "name": "$dataset",
            "quota": $home_quota
        }
        DATA;

        curl_setopt($curl, CURLOPT_POST, 1);  // this is a post request
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  // send data

        curl_setopt($curl, CURLOPT_URL, $path);

        curl_exec($curl);
        if (curl_getinfo($curl)["http_code"] != 200) {
            throw new Exception("Unable to create dataset for home directory");
        }

        // SET DATASET PERMS
        $path = $CONF["host"] . "/pool/dataset/id/" . str_replace('/', '%2F', $dataset) . "/permission";
        $data = <<<DATA
        {
            "user": "$user",
            "group": "$user",
            "options": {
                "recursive": true
            }
        }
        DATA;
        
        curl_setopt($curl, CURLOPT_POST, 1);  // this is a post request
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  // send data

        curl_setopt($curl, CURLOPT_URL, $path);

        curl_exec($curl);
        var_dump(curl_getinfo($curl));
        die();
        /*
        if (!curl_exec($curl)) {
            // roll back changes
            $this->deleteHomeDirectory($user);
            throw new Exception("Unable to update permission for this dataset");
        }*/

        curl_close($curl);  // close session
    }

    public function deleteHomeDirectory($user)
    {
        // This method should be redone for different deployments

        $storage_device = "nas1";
        $CONF = config::STORAGE[$storage_device];
        $curl = unityStorage::getTrueNASCurlObject($CONF["key"]);

        $dataset = $CONF["home_dataset"] . $user;

        $path = $CONF["host"] . "/pool/dataset/id/" . str_replace('/', '%2F', $dataset);
        $data = <<<DATA
        {
            "recursive": true,
            "force": true
        }
        DATA;

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  // send data

        curl_setopt($curl, CURLOPT_URL, $path);
        if (curl_getinfo($curl)["http_code"] != 200) {
            throw new Exception("Unable to delete dataset");
        }

        curl_close($curl);
    }

    public function updateHomeDirectory($user, $size)
    {
        // This method should be redone for different deployments

    }

    public function getHomeDirectory($user) {

    }
}
