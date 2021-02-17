<?php
require "../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form was submitted
    $errors = array();  // This needs to be initialized for the success message to display
    if (empty($_POST["name"])) {
        $errors[] = unity_locale::CONTACT_ERR_NAME;
    }

    if (!filter_var($_POST["mail"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = unity_locale::CONTACT_ERR_MAIL;
    }

    if (empty($_POST["subject"])) {
        $errors[] = unity_locale::CONTACT_ERR_SUBJECT;
    }

    if (empty($_POST["message"])) {
        $errors[] = unity_locale::CONTACT_ERR_MESSAGE;
    }

    if (count($errors) == 0) {
        // No errors, you may proceed
        $mailer->send("contact_form", $_POST);
    }
}
?>

<h1><?php echo unity_locale::CONTACT_HEADER_MAIN; ?></h1>

<form id="contact-form" action="" method="POST" onSubmit="return confirm('<?php echo unity_locale::CONTACT_WARN_SEND; ?>');">
    <?php
    echo "<input type='text' name='name' placeholder='" . unity_locale::LABEL_NAME . "'";
    if (isset($_SESSION["SHIB"])) {
        echo " value='" . $_SESSION["SHIB"]["name"] . "'>";
    } else {
        echo ">";
    }

    echo "<input type='text' name='mail' placeholder='" . unity_locale::LABEL_MAIL . "'";
    if (isset($_SESSION["SHIB"])) {
        echo " value='" . $_SESSION["SHIB"]["mail"] . "'>";
    } else {
        echo ">";
    }
    ?>
    <input type="text" name="subject" placeholder="<?php echo unity_locale::LABEL_SUBJECT; ?>">
    <textarea placeholder="<?php echo unity_locale::LABEL_MESSAGE; ?>" name="message" form="contact-form"></textarea>
    <input type="submit" value="<?php echo unity_locale::CONTACT_LABEL_SEND; ?>">
</form>

<?php
printMessages($errors, unity_locale::CONTACT_MES_SENT);

require_once config::PATHS["templates"] . "/footer.php";
?>