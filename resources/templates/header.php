<?php

use UnityWebPortal\lib\UnitySite;

if ((@$_SESSION["is_admin"] ?? false) == true
    && $_SERVER["REQUEST_METHOD"] == "POST"
    && (@$_POST["form_name"] ?? null) == "clearView"
) {
    unset($_SESSION["viewUser"]);
    UnitySite::redirect($CONFIG["site"]["prefix"] . "/admin/user-mgmt.php");
}

if (isset($SSO)) {
    if (!$_SESSION["user_exists"]) {
        UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/new_account.php");
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
    foreach ($CONFIG["colors"] as $var_name => $var_value) {
        echo "--$var_name: $var_value;";
    }
    echo "}";
    ?>
  </style>

  <link rel="stylesheet" type="text/css" href="<?php echo $CONFIG["site"]["prefix"]; ?>/css/global.css">
  <link rel="stylesheet" type="text/css" href="<?php echo $CONFIG["site"]["prefix"]; ?>/css/navbar.css">
  <link rel="stylesheet" type="text/css" href="<?php echo $CONFIG["site"]["prefix"]; ?>/css/modal.css">
  <link rel="stylesheet" type="text/css" href="<?php echo $CONFIG["site"]["prefix"]; ?>/css/tables.css">
  <link rel="stylesheet" type="text/css" href="<?php echo $CONFIG["site"]["prefix"]; ?>/css/filters.css">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo $CONFIG["site"]["description"] ?>">

  <title><?php echo $CONFIG["site"]["name"]; ?></title>
</head>

<body>

  <header>
    <img id="imgLogo" draggable=false 
    src="<?php echo $CONFIG["site"]["prefix"]; ?>/assets/<?php echo $CONFIG["site"]["logo"]; ?>">
    <button class="hamburger vertical-align">
      <img draggable="false" src="<?php echo $CONFIG["site"]["prefix"]; ?>/assets/menu.png" alt="Menu Button">
    </button>
  </header>

  <nav class="mainNav">
    <?php
    // Public Items - Always Visible
    echo "<a href='" . $CONFIG["site"]["prefix"] . "/index.php'>Home</a>";

    $num_additional_items = count($CONFIG["menuitems"]["labels"]);
    for ($i = 0; $i < $num_additional_items; $i++) {
        echo "<a target='_blank' href='" . $CONFIG["menuitems"]["links"][$i] . "'>" .
        $CONFIG["menuitems"]["labels"][$i] . "</a>";
    }

    if (isset($_SESSION["user_exists"]) && $_SESSION["user_exists"]) {
      // Menu Items for Present Users
        echo "<a href='" . $CONFIG["site"]["prefix"] . "/panel/support.php'>Support</a>";
        echo "<a href='" . $CONFIG["site"]["prefix"] . "/panel/account.php'>Account Settings</a>";
        echo "<a href='" . $CONFIG["site"]["prefix"] . "/panel/groups.php'>My PIs</a>";

        if (isset($_SESSION["is_pi"]) && $_SESSION["is_pi"]) {
          // PI only pages
            echo "<a href='" . $CONFIG["site"]["prefix"] . "/panel/pi.php'>My Users</a>";
        }

      // additional branding items
        $num_additional_items = count($CONFIG["menuitems_secure"]["labels"]);
        for ($i = 0; $i < $num_additional_items; $i++) {
            echo "<a target='_blank' href='" . $CONFIG["menuitems_secure"]["links"][$i] . "'>" .
            $CONFIG["menuitems_secure"]["labels"][$i] . "</a>";
        }

      // admin pages
        if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] && !isset($_SESSION["viewUser"])) {
            echo "<hr class='navHR'>";
          // Admin only pages
            echo "<a href='" . $CONFIG["site"]["prefix"] . "/admin/user-mgmt.php'>User Management</a>";
            echo "<a href='" . $CONFIG["site"]["prefix"] . "/admin/pi-mgmt.php'>PI Management</a>";
            echo "<a href='" . $CONFIG["site"]["prefix"] . "/admin/notices.php'>Cluster Notices</a>";
            echo "<a href='" . $CONFIG["site"]["prefix"] . "/admin/content.php'>Content Management</a>";
        }
    } else {
        echo "<a href='" . $CONFIG["site"]["prefix"] . "/panel/account.php'>Login / Request Account</a>";
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
      <div class="modalMessages"></div>
      <div class="modalButtons">
        <div class='buttonList messageButtons' style='display: none;'><button class='btnOkay'>Okay</button></div>
        <div class='buttonList yesnoButtons' style='display: none;'>
        <button class='btnYes'>Yes</button>
        <button class='btnNo'>No</button>
      </div>
      </div>
    </div>
  </div>
  <script src="<?php echo $CONFIG["site"]["prefix"]; ?>/js/modal.js"></script>

  <main>

  <?php
    if (isset($_SESSION["is_admin"])
        && $_SESSION["is_admin"]
        && isset($_SESSION["viewUser"])
    ) {
        $viewUser = $_SESSION["viewUser"];
        echo "
          <div id='viewAsBar'>
            <span>You are accessing the web portal as the user <strong>$viewUser</strong></span>
            <form method='POST' action=''>
              <input type='hidden' name='form_name' value='clearView'>
              <input type='hidden' name='uid' value='$viewUser'>
              <input type='submit' value='Return to My User'>
            </form>
          </div>
        ";
    }
    ?>
