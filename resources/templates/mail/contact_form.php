<?php
$this->setFrom(config::MAIL["addresses"]["sender"][0], config::MAIL["addresses"]["sender"][1]);
$this->addReplyTo($data["mail"]);
$this->addAddress(config::MAIL["addresses"]["contact"][0], config::MAIL["addresses"]["contact"][1]);
$this->Subject = "[Unity Form] " . $data["subject"];

echo $data["message"];

include "footer.php";
?>