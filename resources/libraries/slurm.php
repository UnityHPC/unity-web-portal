<?php

/**
 * Class that manages slurm accounts. The apcahe account (www-data by default) MUST be an operator and have a user account in sacctmgr in the cluster specified
 * (this is the sketchiest class)
 */
class slurm
{
    const CMD_PREFIX = "sacctmgr -i ";  // -i disabled the yes/no confirmation

    private $cluster;
    private $logger;

    public function __construct($clustername, $logger)
    {
        $this->logger = $logger;
        $this->cluster = $clustername;
    }

    private function cmd($cmd)
    {
        exec($cmd, $output, $return);

        if ($return != 0) {
            $this->logger->logCritical("$cmd returned error code $return with output " . var_dump($output));
            $this->logger->killPortal();
            throw new Exception("$cmd returned error code $return with output " . var_dump($output));  // this won't execute
        }

        return $output;
    }

    public function addAccount($name)
    {
        if (!$this->accountExists($name)) {
            $this->cmd(self::CMD_PREFIX . "add account $name Cluster=$this->cluster");
        }
    }

    public function deleteAccount($name)
    {
        if ($this->accountExists($name)) {
            $this->cmd(self::CMD_PREFIX . "delete account $name");
        }
    }

    public function addUser($name, $account)
    {
        if (!$this->userExists($name, $account)) {
            $this->cmd(self::CMD_PREFIX . "add user $name Account=$account");
        }
    }

    public function deleteUser($name, $account)
    {
        if ($this->userExists($name, $account)) {
            $this->cmd(self::CMD_PREFIX . "delete user $name where Account=$account");
        }
    }

    public function accountExists($name)
    {
        return count($this->readAssocDB(array("Account=$name"))) > 0;
    }

    public function userExists($name, $account = NULL)
    {
        if (is_null($account)) {
            return count($this->readAssocDB(array("User=$name"))) > 0;
        } else {
            return count($this->readAssocDB(array("User=$name","Account=$account"))) > 0;
        }
    }

    public function getAccountsFromUser($user) {
        $accounts = $this->readAssocDB(array("User=$user"));

        $out = array();
        foreach($accounts as $account) {
            if ($account[1] != "" && $account[1] != "root") {
                array_push($out, $account[1]);  // index 2 is UID
            }
        }

        return $out;
    }

    public function getUsersFromAccount($account) {
        $users = $this->readAssocDB(array("Account=$account"));

        $out = array();
        foreach($users as $user) {
            if ($user[2] != "" && $user[2] != "root") {
                array_push($out, $user[2]);  // index 2 is UID
            }
        }

        return $out;
    }

    public function getAccounts() {
        $accounts_raw = $this->readAccDB();

        $out = array();
        foreach ($accounts_raw as $account_raw) {
            $pi_netid = $account_raw[0];
            if ($pi_netid != "root") {  // disregard root account
                array_push($out, $pi_netid);
            }
        }

        return $out;
    }

    /**
     * Updates the local var with cluster associations
     */
    private function readAssocDB($filters = array()) {
        $query = self::CMD_PREFIX . "-P show associations";  // -P is parsable output

        foreach ($filters as $filter) {
            $query .= " where $filter";
        }

        $associations = $this->cmd($query);
        array_shift($associations);  // Remove the key output

        $out = array();
        foreach($associations as $assoc) {
            $exploded = explode("|", $assoc);
            array_push($out, $exploded);
        }

        return $out;
    }

    private function readAccDB($filters = array()) {
        $query = self::CMD_PREFIX . "-P show accounts";  // -P is parsable output

        foreach ($filters as $filter) {
            $query .= " where $filter";
        }

        $associations = $this->cmd($query);
        array_shift($associations);  // Remove the key output

        $out = array();
        foreach($associations as $assoc) {
            $exploded = explode("|", $assoc);
            array_push($out, $exploded);
        }

        return $out;
    }
}
