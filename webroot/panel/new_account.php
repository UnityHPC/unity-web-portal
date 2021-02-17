<?php

require_once "../../resources/autoload.php";

require_once config::PATHS["templates"] . "/header.php";

if ($_SESSION["user-state"] == "active" && !$user->isAdmin()) {
	redirect("/panel/index.php");  // Redirect if account already exists
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$errors = array();

	if (!isset($_POST["eula"]) || $_POST["eula"] != "agree") {
		// checkbox was not checked
		array_push($errors, "Accepting the EULA is required");
	}

	// Request Account Form was Submitted
	if (count($errors) == 0) {
		try {
			$user->init($SHIB["firstname"],$SHIB["lastname"],$SHIB["mail"],isset($_POST["pi"]) && $_POST["pi"] == "pi");

			if (isset($_POST["pi"]) && $_POST["pi"] == "pi") {
				// send email to admins here
				$mailer->send("new_pi_request", $SHIB);
			}

			redirect(config::PREFIX . "/panel");
		} catch (Exception $e) {
			array_push($errors, unity_locale::ERR . "\n" . $e->getMessage());
		}
	}
}

?>

<h1><?php echo unity_locale::NEWACC_HEADER_MAIN; ?></h1>

<form id="newAccountForm" action="" method="POST">
	<span>Please verify that the information below is correct before continuing</span>
	<div>
		<b>Name&nbsp;&nbsp;</b><?php echo $SHIB["firstname"] . " " . $SHIB["lastname"]; ?><br>
		<b>Email&nbsp;&nbsp;</b><?php echo $SHIB["mail"]; ?>
	</div>
	<span>Your username will be <b><?php echo $SHIB["netid"]; ?></b></span>

	<input type="checkbox" id="chk_pi" name="pi" value="pi">
	<label for="chk_pi">I am a PI (Principal Investigator)</label>

	<br>

	<input type="checkbox" id="chk_eula" name="eula" value="agree">
	<label for="chk_eula">I have read and accept the <a target="_blank" href="<?php echo config::PREFIX; ?>/priv.php">Unity EULA</a></label>
	<input style="margin-top: 10px;" type="submit" value="Create Account">

	<?php
	if (isset($errors)) {
		echo "<div class='message'>";
		foreach ($errors as $err) {
			echo "<span class='message-failure'>" . $err . "</span>";
		}
		echo "</div>";
	}
	?>
</form>

<?php
require_once config::PATHS["templates"] . "/footer.php";
?>