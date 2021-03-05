<?php

class curlRest {

    private $baseURL;
    private $curl;

    public function __construct($baseURL, $headers = NULL, $user = NULL, $pass = NULL) {
        $this->baseURL = $baseURL;
        $this->curl = curl_init();
        
        if (isset($headers)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        }

        if (isset($user) && isset($pass)) {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->curl, CURLOPT_USERPWD, $user . ":" . $pass);
        }
    }

    public function postAPI($path, $data = false) {
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_PUT, 0);

        if ($data) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        }

        $url = $this->baseURL . $path;

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        return json_decode(curl_exec($this->curl), true);
    }

    public function putAPI($path) {
        curl_setopt($this->curl, CURLOPT_POST, 0);
        curl_setopt($this->curl, CURLOPT_PUT, 1);

        $url = $this->baseURL . $path;

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        return json_decode(curl_exec($this->curl), true);
    }

    public function getAPI($path, $data = false) {
        curl_setopt($this->curl, CURLOPT_POST, 0);
        curl_setopt($this->curl, CURLOPT_PUT, 0);

        if ($data) {
            $path = sprintf("%s?%s", $path, http_build_query($data));
        }

        $url = $this->baseURL . $path;

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        return json_decode(curl_exec($this->curl), true);
    }

}