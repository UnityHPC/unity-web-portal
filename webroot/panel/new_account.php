<?php

require_once __DIR__ . "/../../resources/autoload.php";

use UnityWebPortal\lib\UnityHTTPD;
use UnityWebPortal\lib\UnityUser;

if ($USER->exists()) {
    UnityHTTPD::redirect(getURL("/panel/account.php"));
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    UnityHTTPD::validatePostCSRFToken();
    $user = new UnityUser($SSO["user"], $LDAP, $SQL, $MAILER, $WEBHOOK);
    $user->init($SSO["firstname"], $SSO["lastname"], $SSO["mail"], $SSO["org"]);
    // header.php will redirect to this same page again and then this page will redirect to account
}
require $LOC_HEADER;
?>

<h1>Register New Account</h1>
<hr>
<p>Please verify that the information below is correct before continuing</p>
<div>
    <strong>Name&nbsp;&nbsp;</strong>
    <?php echo $SSO["firstname"] . " " . $SSO["lastname"]; ?>
    <br>
    <strong>Email&nbsp;&nbsp;</strong>
    <?php echo $SSO["mail"]; ?>
</div>
<p>Your unity cluster username will be <strong><?php echo $SSO["user"]; ?></strong></p>
<br>
<form action="" method="POST">
    <?php echo UnityHTTPD::getCSRFTokenHiddenFormInput(); ?>
    <input type='submit' value='Register'>
</form>
<?php
require_once $LOC_FOOTER;
