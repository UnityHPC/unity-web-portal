<?php

class serviceStack
{
    const DEFAULT_KEY = "default";

    private $services = array(
        "ldap" => array(),
        "sql" => array(),
        "mail" => array(),
        "storage" => array(),
        "sacctmgr" => array()
    );

    public function __construct()
    {
    }

    public function add_ldap($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["ldap"])) {
            throw new Exception("Service '$name' already exists.");
        }

        $ldap_object = new unityLDAP($details["uri"], $details["bind_dn"], $details["bind_pass"]);
        $this->services["ldap"][$name] = $ldap_object;

        return $this;
    }

    public function add_sql($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["sql"])) {
            throw new Exception("Service '$name' already exists.");
        }

        $sql_object = new unitySQL($details["host"], $details["db"], $details["user"], $details["pass"]);
        $this->services["sql"][$name] = $sql_object;

        return $this;
    }

    public function add_mail($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["mail"])) {
            throw new Exception("Service '$name' already exists.");
        }

        if (!array_key_exists("template_path", $details)) {
            throw new Exception("Template path not set.");
        }
        $mailer = new templateMailer($details["template_path"]);

        $mailer->isSMTP();
        //$mailer->SMTPDebug = 4;  // DEBUG

        if (!array_key_exists("host", $details)) {
            throw new Exception("Hostname not set.");
        }
        $mailer->Host = $details["host"];

        if (!array_key_exists("port", $details)) {
            throw new Exception("Port not set");
        }
        $mailer->Port = $details["port"];

        if (array_key_exists("smtp_options", $details)) {
            $mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        } else {
            $mailer->SMTPOptions = $details["smtp_options"];
        }

        if (array_key_exists("smtp_secure", $details)) {
            $mailer->SMTPSecure = $details["smtp_secure"];
        }

        if (array_key_exists("smtp_user", $details) && array_key_exists("smtp_pass", $details)) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $details["smtp_user"];
            $mailer->Password = $details["smtp_pass"];
        } else {
            $mailer->SMTPAuth = false;
        }

        $this->services["mail"][$name] = $mailer;

        return $this;
    }

    public function add_storage($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["mail"])) {
            throw new Exception("Service '$name' already exists.");
        }

        if (!array_key_exists("type", $details)) {
            throw new Exception("Storage type not set.");
        }

        // this is where storage drivers should be indexed and listed
        switch ($details["type"]) {
            case "local":
                // locally mounted storage device
                if (!array_key_exists("path", $details)) {
                    throw new Exception("Local device requires path to be set in connection_details parameter.");
                }

                $device = new localStorageDriver($details["path"], $details["home"], $details["scratch"], $details["project"]);

                break;
            case "truenas_core":
                // truenas core device via rest API

                if (!array_key_exists("api_key", $details) || !array_key_exists("url", $details)) {
                    throw new Exception("Truenas device requires api_key and url to be set in connection_details parameter.");
                }

                $device = new truenasCoreStorageDriver($details["url"], $details["api_key"], $details["home"], $details["scratch"], $details["project"]);

                break;
            default:
                throw new Exception($details["type"] + " is not a supported storage device type.");
        }

        $this->services["storage"][$name] = $device;

        return $this;
    }

    public function add_sacctmgr($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["mail"])) {
            throw new Exception("Service '$name' already exists.");
        }

        if (!array_key_exists("cluster", $details)) {
            throw new Exception("Slurm cluster name must be set.");
        }

        $sacctmgr = new slurm($details["cluster"]);

        $this->services["sacctmgr"][$name] = $sacctmgr;

        return $this;
    }

    public function ldap($name = self::DEFAULT_KEY)
    {
        return $this->services["ldap"][$name];
    }

    public function sql($name = self::DEFAULT_KEY)
    {
        return $this->services["sql"][$name];
    }

    public function mail($name = self::DEFAULT_KEY)
    {
        return $this->services["mail"][$name];
    }

    public function storage($name = self::DEFAULT_KEY)
    {
        return $this->services["storage"][$name];
    }

    public function sacctmgr($name = self::DEFAULT_KEY)
    {
        return $this->services["sacctmgr"][$name];
    }
}
