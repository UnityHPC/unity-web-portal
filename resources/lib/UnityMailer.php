<?php

namespace UnityWebPortal\lib;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;

/**
 * This is a class that uses PHPmailer to send emails based on templates
 */
class UnityMailer extends PHPMailer
{
    private $template_dir;  // location of all email templates

    private $MSG_LINKREF;
    private $MSG_SENDER_EMAIL;
    private $MSG_SENDER_NAME;
    private $MSG_SUPPORT_EMAIL;
    private $MSG_SUPPORT_NAME;
    private $MSG_ADMIN_EMAIL;
    private $MSG_ADMIN_NAME;
    private $MSG_PI_APPROVAL_EMAIL;
    private $MSG_PI_APPROVAL_NAME;

    public function __construct(
        $template_dir,
        $hostname,
        $port,
        $security,
        $user,
        $pass,
        $ssl_verify,
        $msg_linkref,
        $msg_sender_email,
        $msg_sender_name,
        $msg_support_email,
        $msg_support_name,
        $msg_admin_email,
        $msg_admin_name,
        $msg_pi_approval_email,
        $msg_pi_approval_name
    ) {
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

        $this->MSG_LINKREF = $msg_linkref;
        $this->MSG_SENDER_EMAIL = $msg_sender_email;
        $this->MSG_SENDER_NAME = $msg_sender_name;
        $this->MSG_SUPPORT_EMAIL = $msg_support_email;
        $this->MSG_SUPPORT_NAME = $msg_support_name;
        $this->MSG_ADMIN_EMAIL = $msg_admin_email;
        $this->MSG_ADMIN_NAME = $msg_admin_name;
        $this->MSG_PI_APPROVAL_EMAIL = $msg_pi_approval_email;
        $this->MSG_PI_APPROVAL_NAME = $msg_pi_approval_name;
    }

    public function sendMail($recipients, $template = null, $data = null)
    {
        if (isset($template)) {
            // set addresses
            $this->setFrom($this->MSG_SENDER_EMAIL, $this->MSG_SENDER_NAME);
            $this->addReplyTo($this->MSG_SUPPORT_EMAIL, $this->MSG_SUPPORT_NAME);


            ob_start();
            include $this->template_dir . "/" . $template . ".php";
            include $this->template_dir . "/footer.php";
            $mes_html = ob_get_clean();
            $this->msgHTML($mes_html);

            if ($recipients == "admin") {
                $this->addBCC($this->MSG_ADMIN_EMAIL, $this->MSG_ADMIN_NAME);
            } elseif ($recipients == "pi_approve_email") {
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
            // Clear addresses
            $this->clearAllRecipients();
            return true;
        } else {
            return false;
        }
    }
}
