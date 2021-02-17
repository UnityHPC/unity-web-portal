<?php
$this->setFrom(config::MAIL["addresses"]["sender"][0], config::MAIL["addresses"]["sender"][1]);
$this->addReplyTo(config::MAIL["addresses"]["contact"][0], config::MAIL["addresses"]["contact"][1]);
$this->addAddress($data["to"]);
$this->Subject = unity_locale::MAIL_HEADER_PIDENY;

echo unity_locale::MAIL_LABEL_PIDENY($data["group"]);

include "footer.php";
?>