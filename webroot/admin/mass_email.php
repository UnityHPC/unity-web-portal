<?php
require "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $errors = array();
  // Form was submitted
  $data["subject"] = $_POST["subject"];
  if ($data["subject"] == "") {
    array_push($errors, unity_locale::MASS_ERR_SUBJECT);
  }

  $data["body"] = $_POST["body"];
  if ($data["body"] == "") {
    array_push($errors, unity_locale::MASS_ERR_MESSAGE);
  }

  $data["bcc"] = $ldap->getAllRecipients();

  // DEBUG
  //$data["bcc"] = array(
  //    "hsaplakoglu@umass.edu",
  //    "hakansaplakog@gmail.com"
  //);

  if (count($errors) == 0) {
    if (!$mailer->send("mass_email", $data)) {
      array_push($errors, unity_locale::ERR);
    }
  }
}

?>

<h1><?php echo unity_locale::MASS_HEADER_MAIN; ?></h1>

<form method="POST" action="">
  <input type="text" name="subject" placeholder="<?php echo unity_locale::LABEL_SUBJECT; ?>">
  <textarea name="body" id="editor"></textarea>
  <input style="margin-top: 10px;" type="submit" onclick="return confirm('<?php echo unity_locale::MASS_WARN_SEND; ?>');" value="<?php echo unity_locale::MASS_LABEL_SEND; ?>">
</form>

<?php
if (isset($errors)) {
  echo "<div class='message'>";
  if (empty($errors)) {
    // success message
    echo "<span class='message-success'>" . unity_locale::MASS_MES_SEND . "</span>";
  } else {
    foreach ($errors as $err) {
      echo "<span class='message-failure'>" . $err . "</span>";
    }
  }
  echo "</div>";
}
?>

<script src="/js/ckeditor.js"></script>
<script>
  ClassicEditor
    .create(document.querySelector('#editor'))
    .then(editor => {
      console.log(editor);
    })
    .catch(error => {
      console.error(error);
    });
</script>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>
