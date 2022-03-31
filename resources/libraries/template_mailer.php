<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * This is a class that uses PHPmailer to send emails based on templates
 */
class templateMailer extends PHPMailer {
    private $template_dir;

    private $logger;

    public function __construct($template_dir, $logger) {
        parent::__construct();

        $this->logger = $logger;

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
