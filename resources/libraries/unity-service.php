<?php



class serviceStack
{
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

    public function add_ldap($name, $bind_dn, $pass, $uri)
    {
        $ldap_object = new unityLDAP($uri, $bind_dn, $pass);
        $this->services["ldap"][$name] = $ldap_object;

        return $this;
    }

    public function add_sql($name, $host, $user, $pass, $db)
    {
        $sql_object = new unitySQL($host, $db, $user, $pass);
        $this->services["sql"][$name] = $sql_object;

        return $this;
    }

    public function add_mail($name, $template_path, $host, $port = 25, $smtp_options = NULL, $smtp_secure = NULL, $smtp_auth = NULL, $smtp_user = NULL, $smtp_pass = NULL)
    {
        $mailer = new templateMailer($template_path);

        // Configure SMTP connection
        $mailer->isSMTP();

        if ($smtp_options == NULL) {
            $mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        } else {
            $mailer->SMTPOptions = $smtp_options;
        }

        //$mailer->SMTPDebug = 4;  // DEBUG
        $mailer->Host = config::MAIL["host"];
        $mailer->Port = $port;

        if ($smtp_secure != NULL) {
            $mailer->SMTPSecure = $smtp_secure;
        }

        if ($smtp_auth != NULL && $smtp_auth) {
            $mailer->SMTPAuth = true;

            if ($smtp_user == NULL || $smtp_pass == NULL) {
                throw new Exception("SMTP Auth is true but credentials were not passed.");
            }

            $mailer->Username = $smtp_user;
            $mailer->Password = $smtp_pass;
        }

        $this->services["mail"][$name] = $mailer;

        return $this;
    }

    public function add_storage($name, $type, $home = NULL, $scratch = NULL, $project = NULL, $connection_details = NULL)
    {
        switch ($type) {
            case "local":
                // locally mounted storage device
                if (!array_key_exists("path", $connection_details)) {
                    throw new Exception("Local device requires path to be set in connection_details parameter.");
                }

                $device = new localStorageDriver($connection_details["path"], $home, $scratch, $project);

                break;
            case "truenas_core":
                // truenas core device via rest API

                if (!array_key_exists("api_key", $connection_details) || !array_key_exists("url", $connection_details)) {
                    throw new Exception("Truenas device requires api_key and url to be set in connection_details parameter.");
                }

                $device = new truenasCoreStorageDriver($connection_details["url"], $connection_details["api_key"], $home, $scratch, $project);

                break;
            default:
                throw new Exception($type + " is not a supported storage device type.");
        }

        $this->services["storage"][$name] = $device;

        return $this;
    }

    public function add_sacctmgr($name, $cluster)
    {
        $sacctmgr = new slurm($cluster);

        $this->services["sacctmgr"][$name] = $sacctmgr;

        return $this;
    }

    public function ldap($name)
    {
        return $this->services["ldap"][$name];
    }

    public function sql($name)
    {
        return $this->services["sql"][$name];
    }

    public function mail($name)
    {
        return $this->services["mail"][$name];
    }

    public function storage($name)
    {
        return $this->services["storage"][$name];
    }

    public function sacctmgr($name)
    {
        return $this->services["sacctmgr"][$name];
    }
}
