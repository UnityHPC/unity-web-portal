<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * This is a class that uses PHPmailer to send emails based on templates
 */
class UnityMailer extends PHPMailer {
    private $template_dir;  // location of all email templates

    public function __construct($template_dir, $hostname, $port, $security, $user, $pass, $ssl_verify) {
        parent::__construct();
        $this->isSMTP();

        if (empty($hostname)) {
            throw new Exception("SMTP server hostname not set");
        }
        $this->Host = $hostname;

        if (empty($port)) {
            throw new Exception("SMTP server port not set");
        }
        $this->Port = $port;

        $security_conf_valid = empty($security) || $security == "tls" || $security == "ssl";
        if (!$security_conf_valid) {
            throw new Exception("SMTP security is not set correctly, leave empty, use 'tls', or 'ssl'");
        }
        $this->SMTPSecure = $security;

        if (!empty($user)) {
            // smtp username provided
            $this->SMTPAuth = true;
            $this->Username = $user;
        } else {
            $this->SMTPAuth = false;
        }

        if (!empty($pass)) {
            // smtp password provided
            $this->Password = $pass;
        }

        if ($ssl_verify == "false") {
            $this->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }

        $this->template_dir = $template_dir;
    }

    public function send($template = null, $data = null) {
        if (isset($template)) {
            ob_start();
            require_once $this->template_dir . "/" . $template . ".php";
            $mes_html = ob_get_clean();
            $this->msgHTML($mes_html);
        }

        if (parent::send()) {
            // Clear addresses
            $this->clearAllRecipients();
            return true;
        } else {
            return false;
        }
    }
}

?>
