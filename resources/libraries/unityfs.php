<?php

class unityfs {
    private $s;

    private $logger;

    public function __construct($host, $port, $logger)
    {
        $this->logger = $logger;

        $this->s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->s, SOL_SOCKET, SO_REUSEADDR, 1);
        if (!socket_connect($this->s, $host, $port)) {
            throw new Exception("Unable to connect to socket");
        }
    }

    public function createHomeDirectory($uid, $quota) {
        $request = "<request type='create_home'>";
        $request .= "<uid>$uid</uid><gid>$uid</gid>";
        $request .= "<quota>$quota</quota>";
        $request .= "</request>";

        if (!socket_send($this->s, $request, strlen($request), 0)) {
            throw new Exception("Could not send data to unityfs");
        }

        $result = socket_read($this->s, 1024);
        return $result;
    }

    public function populateHomeDirectory($uid) {
        $request = "<request type='populate_home'>";
        $request .= "<location>/home/$uid</location>";
        $request .= "<uid>$uid</uid><gid>$uid</gid>";
        $request .= "<scratch>/scratch/$uid</scratch>";
        $request .= "</request>";

        if (!socket_send($this->s, $request, strlen($request), 0)) {
            throw new Exception("Could not send data to unityfs");
        }

        $result = socket_read($this->s, 1024);
        return $result;
    }

    public function createScratchDirectory($uid) {
        $request = "<request type='create_scratch'>";
        $request .= "<uid>$uid</uid><gid>$uid</gid>";
        $request .= "</request>";

        if (!socket_send($this->s, $request, strlen($request), 0)) {
            throw new Exception("Could not send data to unityfs");
        }

        $result = socket_read($this->s, 1024);
        return $result;
    }

    public function populateScratchDirectory($uid) {
        $request = "<request type='populate_scratch'>";
        $request .= "<location>/scratch/$uid</location>";
        $request .= "<uid>$uid</uid><gid>$uid</gid>";
        $request .= "</request>";

        if (!socket_send($this->s, $request, strlen($request), 0)) {
            throw new Exception("Could not send data to unityfs");
        }

        $result = socket_read($this->s, 1024);
        return $result;
    }

    public function close() {
        socket_close($this->s);
    }
}