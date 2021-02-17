<?php
$this->setFrom(config::MAIL["addresses"]["sender"][0], config::MAIL["addresses"]["sender"][1]);
$this->addReplyTo(config::MAIL["addresses"]["contact"][0], config::MAIL["addresses"]["contact"][1]);
$this->addAddress($data["to"]);
$this->Subject = unity_locale::MAIL_HEADER_LEFT_PI;

echo "<p>" . unity_locale::MAIL_LABEL_LEFT_PI . "</p>";

echo "<p><b>" . unity_locale::LABEL_USERID . "</b> " . $data["netid"] . "</p>";
echo "<p><b>" . unity_locale::LABEL_NAME . "</b> " . $data["firstname"] . " " . $data["lastname"] . "</p>";
echo "<p><b>" . unity_locale::LABEL_MAIL . "</b> " . $data["mail"] . "</p>";

include "footer.php";
?>