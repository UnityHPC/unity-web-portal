<?php

namespace UnityWebPortal\lib;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;

/**
 * This is a class that uses PHPmailer to send emails based on templates
 */
class UnityMailer extends PHPMailer
{
    private $template_dir = __DIR__ . "/../mail"; // location of all email templates
    private $override_template_dir = __DIR__ . "/../../deployment/mail_overrides";
    private $MSG_LINKREF = CONFIG["site"]["url"] . CONFIG["site"]["prefix"];
    private $MSG_SENDER_EMAIL = CONFIG["mail"]["sender"];
    private $MSG_SENDER_NAME = CONFIG["mail"]["sender_name"];
    private $MSG_SUPPORT_EMAIL = CONFIG["mail"]["support"];
    private $MSG_SUPPORT_NAME = CONFIG["mail"]["support_name"];
    private $MSG_ADMIN_EMAIL = CONFIG["mail"]["admin"];
    private $MSG_ADMIN_NAME = CONFIG["mail"]["admin_name"];
    private $MSG_PI_APPROVAL_EMAIL = CONFIG["mail"]["pi_approve"];
    private $MSG_PI_APPROVAL_NAME = CONFIG["mail"]["pi_approve_name"];

    public function __construct()
    {
        parent::__construct();
        $this->isSMTP();

        if (empty(CONFIG["smtp"]["host"])) {
            throw new Exception("SMTP server hostname not set");
        }
        $this->Host = CONFIG["smtp"]["host"];

        if (empty(CONFIG["smtp"]["port"])) {
            throw new Exception("SMTP server port not set");
        }
        $this->Port = CONFIG["smtp"]["port"];

        $security = CONFIG["smtp"]["security"];
        $security_conf_valid = empty($security) || $security == "tls" || $security == "ssl";
        if (!$security_conf_valid) {
            throw new Exception(
                "SMTP security is not set correctly, leave empty, use 'tls', or 'ssl'"
            );
        }
        $this->SMTPSecure = $security;

        if (!empty(CONFIG["smtp"]["user"])) {
            $this->SMTPAuth = true;
            $this->Username = CONFIG["smtp"]["user"];
        } else {
            $this->SMTPAuth = false;
        }

        if (!empty(CONFIG["smtp"]["pass"])) {
            $this->Password = CONFIG["smtp"]["pass"];
        }

        if (CONFIG["smtp"]["ssl_verify"] == "false") {
            $this->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }
    }

    public function sendMail($recipients, $template = null, $data = null)
    {
        if (isset($template)) {
            $this->setFrom($this->MSG_SENDER_EMAIL, $this->MSG_SENDER_NAME);
            $this->addReplyTo($this->MSG_SUPPORT_EMAIL, $this->MSG_SUPPORT_NAME);

            $template_filename = $template . ".php";
            if (file_exists($this->override_template_dir . "/" . $template_filename)) {
                $template_path = $this->override_template_dir . "/" . $template_filename;
            } else {
                $template_path = $this->template_dir . "/" . $template_filename;
            }

            if (file_exists($this->override_template_dir . "/footer.php")) {
                $footer_template_path = $this->override_template_dir . "/footer.php";
            } else {
                $footer_template_path = $this->template_dir . "/footer.php";
            }

            ob_start();
            include $template_path;
            include $footer_template_path;
            $mes_html = ob_get_clean();
            $this->msgHTML($mes_html);

            if ($recipients == "admin") {
                $this->addBCC($this->MSG_ADMIN_EMAIL, $this->MSG_ADMIN_NAME);
            } elseif ($recipients == "pi_approve") {
                $this->addBCC($this->MSG_PI_APPROVAL_EMAIL, $this->MSG_PI_APPROVAL_NAME);
            } else {
                if (is_array($recipients)) {
                    foreach ($recipients as $addr) {
                        $this->addBCC($addr);
                    }
                } else {
                    $this->addBCC($recipients);
                }
            }
        }

        if (parent::send()) {
            $this->clearAllRecipients();
            return true;
        } else {
            return false;
        }
    }
}
