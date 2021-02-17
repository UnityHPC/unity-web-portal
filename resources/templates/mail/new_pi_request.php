<?php
$this->setFrom(config::MAIL["addresses"]["sender"][0], config::MAIL["addresses"]["sender"][1]);
$this->addAddress(config::MAIL["addresses"]["contact"][0], config::MAIL["addresses"]["contact"][1]);
$this->Subject = unity_locale::MAIL_HEADER_ADREQUEST;

echo "<p>" . unity_locale::MAIL_LABEL_ADREQUEST . "</p>";

echo "<p><b>" . unity_locale::LABEL_USERID . "</b> " . $data["netid"] . "</p>";
echo "<p><b>" . unity_locale::LABEL_NAME . "</b> " . $data["firstname"] . " " . $data["lastname"] . "</p>";
echo "<p><b>" . unity_locale::LABEL_MAIL . "</b> " . $data["mail"] . "</p>";

echo "<p>" . unity_locale::MAIL_MES_ACTIVATE(config::URL . "/admin/user-mgmt.php") . "</p>";

include "footer.php";
?>