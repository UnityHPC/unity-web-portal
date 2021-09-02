<?php



class serviceStack {
    private $services = array(
        "ldap" => array(),
        "sql" => array(),
        "mail" => array(),
        "storage" => array(),
        "sacctmgr" => array()
    );

    public function __construct() {}

    public function add_ldap($name, $bind_dn, $pass, $uri) {
        $ldap_object = new unityLDAP($uri, $bind_dn, $pass);
        $this->services["ldap"][$name] = $ldap_object;

        return $this;
    }

    public function add_sql($name, $host, $user, $pass, $db) {
        $sql_object = new unitySQL($host, $db, $user, $pass);
        $this->services["sql"][$name] = $sql_object;

        return $this;
    }

    public function add_mail($name, $host, $port = 25, $smtp_options = NULL, $smtp_secure = NULL, $smtp_auth = NULL, $smtp_user = NULL, $smtp_pass = NULL) {



        return $this;
    }

    public function add_storage() {
        return $this;
    }

    public function add_sacctmgr($name, $cluster) {
        return $this;
    }

    public function ldap() {

    }

    public function sql() {

    }

    public function mail() {

    }

    public function storage() {

    }

    public function sacctmgr() {

    }
}