<?php

use UnityWebPortal\lib\UnityHTTPD;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (
        ($_SESSION["is_admin"] ?? false) == true
        && ($_POST["form_type"] ?? null) == "clearView"
    ) {
        unset($_SESSION["viewUser"]);
        UnityHTTPD::redirect(CONFIG["site"]["prefix"] . "/admin/user-mgmt.php");
    }
    // Webroot files need to handle their own POSTs before loading the header
    // so that they can do UnityHTTPD::badRequest before anything else has been printed.
    // They also must not redirect like standard PRG practice because this
    // header also needs to handle POST data. So this header does the PRG redirect
    // for all pages.
    unset($_POST); // unset ensures that header must not come before POST handling
    UnityHTTPD::redirect();
}

if (isset($SSO)) {
    if (
        !$_SESSION["user_exists"]
        && !str_ends_with($_SERVER['PHP_SELF'], "/panel/new_account.php")
    ) {
        UnityHTTPD::redirect(CONFIG["site"]["prefix"] . "/panel/new_account.php");
    }
}

?>

<!DOCTYPE html>
<html>

<head>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
  <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>

  <style>
    <?php
    // set global css variables from branding
    echo ":root {";
    foreach (CONFIG["colors"] as $var_name => $var_value) {
        echo "--$var_name: $var_value;";
    }
    echo "}";
    ?>
  </style>

  <?php
    $prefix = CONFIG["site"]["prefix"];
    echo "
        <link rel='stylesheet' type='text/css' href='$prefix/css/global.css'>
        <link rel='stylesheet' type='text/css' href='$prefix/css/navbar.css'>
        <link rel='stylesheet' type='text/css' href='$prefix/css/modal.css'>
        <link rel='stylesheet' type='text/css' href='$prefix/css/tables.css'>
        <link rel='stylesheet' type='text/css' href='$prefix/css/filters.css'>
        <link rel='stylesheet' type='text/css' href='$prefix/css/messages.css'>
    ";
    ?>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo CONFIG["site"]["description"] ?>">

  <title><?php echo CONFIG["site"]["name"]; ?></title>
</head>

<body>

  <header>
    <img id="imgLogo" draggable=false
    src="<?php echo CONFIG["site"]["prefix"]; ?>/assets/<?php echo CONFIG["site"]["logo"]; ?>">
    <button class="hamburger vertical-align">
      <img
        draggable="false"
        src="<?php echo CONFIG["site"]["prefix"]; ?>/assets/menu.png"
        alt="Menu Button"
      >
    </button>
  </header>

  <nav class="mainNav">
    <?php
    $prefix = CONFIG["site"]["prefix"];
    // Public Items - Always Visible
    echo "<a href='$prefix/index.php'>Home</a>";

    $num_additional_items = count(CONFIG["menuitems"]["labels"]);
    for ($i = 0; $i < $num_additional_items; $i++) {
        echo "<a target='_blank' href='" . CONFIG["menuitems"]["links"][$i] . "'>" .
        CONFIG["menuitems"]["labels"][$i] . "</a>";
    }

    if (isset($_SESSION["user_exists"]) && $_SESSION["user_exists"]) {
        // Menu Items for Present Users
        echo "<a href='$prefix/panel/support.php'>Support</a>";
        echo "<a href='$prefix/panel/account.php'>Account Settings</a>";
        echo "<a href='$prefix/panel/groups.php'>My PIs</a>";

        if (isset($_SESSION["is_pi"]) && $_SESSION["is_pi"]) {
            // PI only pages
            echo "<a href='$prefix/panel/pi.php'>My Users</a>";
        }

        // additional branding items
        $num_additional_items = count(CONFIG["menuitems_secure"]["labels"]);
        for ($i = 0; $i < $num_additional_items; $i++) {
            echo "<a target='_blank' href='" . CONFIG["menuitems_secure"]["links"][$i] . "'>" .
            CONFIG["menuitems_secure"]["labels"][$i] . "</a>";
        }

        // admin pages
        if (
            isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] && !isset($_SESSION["viewUser"])
        ) {
            echo "<hr class='navHR'>";
            // Admin only pages
            echo "<a href='$prefix/admin/user-mgmt.php'>User Management</a>";
            echo "<a href='$prefix/admin/pi-mgmt.php'>PI Management</a>";
            echo "<a href='$prefix/admin/notices.php'>Cluster Notices</a>";
            echo "<a href='$prefix/admin/content.php'>Content Management</a>";
        }
    } else {
        echo "<a href='$prefix/panel/account.php'>Login / Request Account</a>";
    }
    ?>
  </nav>

  <div class="modalWrapper" style="display: none;">
    <div class="modalContent">
      <div class="modalTitleWrapper">
        <span class="modalTitle"></span>
        <button style="position: absolute; right: 10px; top: 10px;" class="btnClose"></button>
      </div>
      <div class="modalBody"></div>
    </div>
  </div>
  <script src="<?php echo CONFIG["site"]["prefix"]; ?>/js/modal.js"></script>

  <main>

  <?php
    foreach (UnityHTTPD::getMessages() as [$title, $body, $level]) {
        echo sprintf(
            "
              <div class='message %s'>
                <h3>%s</h3>
                <p>%s</p>
                <button onclick=\"this.parentElement.style.display='none';\">×</button>
              </div>
            ",
            $level->value,
            strip_tags($title),
            strip_tags($body)
        );
    }
    UnityHTTPD::clearMessages();
    if (
        isset($_SESSION["is_admin"])
        && $_SESSION["is_admin"]
        && isset($_SESSION["viewUser"])
    ) {
        $viewUser = $_SESSION["viewUser"];
        echo "
          <div id='viewAsBar'>
            <span>You are accessing the web portal as the user <strong>$viewUser</strong></span>
            <form method='POST' action=''>
              <input type='hidden' name='form_type' value='clearView'>
              <input type='hidden' name='uid' value='$viewUser'>
              <input type='submit' value='Return to My User'>
            </form>
          </div>
        ";
    }
    ?>
