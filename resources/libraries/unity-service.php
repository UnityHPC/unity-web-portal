<?php

class serviceStack
{
    const DEFAULT_KEY = "default";

    private $logger;

    private $services = array(
        "ldap" => array(),
        "sql" => array(),
        "mail" => array()
    );

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function add_ldap($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["ldap"])) {
            throw new Exception("Service '$name' already exists.");
        }

        $ldap_object = new unityLDAP($details["uri"], $details["bind_dn"], $details["bind_pass"], $this->logger);
        $this->services["ldap"][$name] = $ldap_object;

        return $this;
    }

    public function add_sql($details, $name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["sql"])) {
            throw new Exception("Service '$name' already exists.");
        }

        $sql_object = new unitySQL($details["host"], $details["db"], $details["user"], $details["pass"], $this->logger);
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
        $mailer = new templateMailer($details["template_path"], $this->logger);

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

        if (!array_key_exists("smtp_options", $details)) {
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

    public function ldap($name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["ldap"])) {
            return $this->services["ldap"][$name];
        } else {
            return NULL;
        }
    }

    public function sql($name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["sql"])) {
            return $this->services["sql"][$name];
        } else {
            return NULL;
        }
    }

    public function mail($name = self::DEFAULT_KEY)
    {
        if (array_key_exists($name, $this->services["mail"])) {
            return $this->services["mail"][$name];
        } else {
            return NULL;
        }
    }
}
