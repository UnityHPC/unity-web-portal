<?php
/* REQUIRES /resources/config.php */

//
// Setup SMTP connection
//
require_once config::PATHS["libraries"] . "/template_mailer.php";
$mailer = new templateMailer(config::PATHS["templates"] . "/mail");

// Configure SMTP connection to UMASS mailhub
$mailer->isSMTP();
$mailer->SMTPOptions = array(
  'ssl' => array(
    'verify_peer' => false,
    'verify_peer_name' => false,
    'allow_self_signed' => true
  )
);
//$mailer->SMTPDebug = 4;  // DEBUG
$mailer->Host = config::MAIL["host"];
$mailer->Port = 465;
$mailer->SMTPSecure = "ssl";
$mailer->SMTPAuth = true;
$mailer->Username = config::MAIL["user"];
$mailer->Password = config::MAIL["pass"];