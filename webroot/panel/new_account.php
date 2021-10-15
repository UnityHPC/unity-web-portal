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

	if (isset($_POST["acc_type"]) && $_POST["acc_type"] == "pi") {
		// send email to admins here
		$SERVICES->mail()->send("new_pi_request", $SHIB);
	} else {
		array_push($errors, "Please select your role");
	}

	// Request Account Form was Submitted
	if (count($errors) == 0) {
		try {
			$user->init($SHIB["firstname"],$SHIB["lastname"],$SHIB["mail"],isset($_POST["pi"]) && $_POST["pi"] == "pi");

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
	<span>Your unity cluster username will be <b><?php echo $SHIB["netid"]; ?></b></span>

	<input type="radio" id="btn_yes_pi" name="acc_type" value="pi">
	<label for="btn_yes_pi">I am a principal investigator (PI)</label><br>
	<input type="radio" id="btn_researcher" name="acc_type" value="research">
	<label for="btn_researcher">I am a researcher</label><br>
	<input type="radio" id="btn_student" name="acc_type" value="student">
	<label for="btn_student">I am a student in a class</label><br>

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