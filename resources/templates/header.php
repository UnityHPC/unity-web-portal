<?php

if (isset($SHIB)) {
  if (!$_SESSION["user_exists"]) {
    redirect(config::PREFIX . "/panel/new_account.php");
  }
}

?>

<!DOCTYPE html>
<html>

<head>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/vars.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/global.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/navbar.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/modal.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/tables.css">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo config::CLUSTER["desc"]; ?>">

  <title><?php echo config::CLUSTER["name"]; ?> &#8226; <?php echo config::CLUSTER["org"]; ?></title>
</head>

<body>

  <header>
    <img id="imgLogo" draggable=false src="<?php echo config::PREFIX; ?>/res/logo.png">
    <a href="<?php echo config::APP["repo"]; ?>" target="_blank" class="unity-state">Beta</a>
    <button class="hamburger vertical-align"><img draggable="false" src="<?php echo config::PREFIX; ?>/res/menu.png" alt="Menu Button"></button>
  </header>

  <nav class="mainNav">
    <?php
    // Public Items - Always Visible
    echo "<a href='" . config::PREFIX . "/index.php'>About</a>";
    echo "<a target='_blank' href='" . config::DOCS_URL . "'>Documentation</a>";

    if (isset($_SESSION["user_exists"]) && $_SESSION["user_exists"]) {
      // Menu Items for Present Users
      echo "<a href='" . config::PREFIX . "/panel/support.php'>Support</a>";
      echo "<a href='" . config::PREFIX . "/panel/account.php'>Account Settings</a>";
      echo "<a href='" . config::PREFIX . "/panel/groups.php'>My PIs</a>";

      if (isset($_SESSION["is_pi"]) && $_SESSION["is_pi"]) {
        // PI only pages
        echo "<a href='" . config::PREFIX . "/panel/pi.php'>My Users</a>";
      }

      if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) {
        // Admin only pages
        echo "<a href='" . config::PREFIX . "/admin/user-mgmt.php'>User Management</a>";
      }
      echo "<a target='_blank' href='/panel/jhub'>JupyterLab</a>";
    } else {
      echo "<a href='" . config::PREFIX . "/panel/account.php'>Login / Request Account</a>";
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
        <div class='buttonList yesnoButtons' style='display: none;'><button class='btnYes'>Yes</button><button class='btnNo'>No</button></div>
      </div>
    </div>
  </div>
  <script src="<?php echo config::PREFIX; ?>/js/modal.js"></script>

  <main>
