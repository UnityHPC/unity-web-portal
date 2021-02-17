<?php
$this->setFrom(config::MAIL["addresses"]["sender"][0], config::MAIL["addresses"]["sender"][1]);
$this->addReplyTo(config::MAIL["addresses"]["contact"][0], config::MAIL["addresses"]["contact"][1]);
foreach($data["bcc"] as $recip) {
  $this->addBcc($recip);
}
$this->Subject = $data["subject"];

echo $data["body"];

include "footer.php";
?>