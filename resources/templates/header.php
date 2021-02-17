<!DOCTYPE html>
<html>

<head>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/vars.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/global.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/navbar.css">
  <link rel="stylesheet" type="text/css" href="<?php echo config::PREFIX; ?>/css/modal.css">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?php echo config::CLUSTER["desc"]; ?>">

  <title><?php echo config::CLUSTER["name"]; ?> &#8226; <?php echo config::CLUSTER["org"]; ?></title>
</head>

<body>

  <header>
    <img id="imgLogo" draggable=false src="<?php echo config::PREFIX; ?>/res/logo.png">
    <a href="/docs/roadmap" target="_blank" class="unity-state">Beta</a>
    <button class="hamburger vertical-align"><img draggable="false" src="<?php echo config::PREFIX; ?>/res/menu.png" alt="Menu Button"></button>
  </header>

  <nav class="mainNav">
    <?php
    // Public Items - Always Visible
    echo "<a href='" . config::PREFIX . "/index.php'>About</a>";
    echo "<a href='" . config::PREFIX . "/contact.php'>Contact</a>";
    echo "<a target='_blank' href='/docs'>Documentation</a>";
    //echo "<a href='" . config::PREFIX . "/cluster-status.php'>Cluster Status</a>";

    if (isset($_SESSION["user-state"]) && $_SESSION["user-state"] == "present") {
      // Menu Items for Present Users
      echo "<a href='" . config::PREFIX . "/panel/account.php'>Account Settings</a>";
      echo "<a href='" . config::PREFIX . "/panel/groups.php'>My Groups</a>";

      if ($_SESSION["is_pi"]) {
        echo "<a href='" . config::PREFIX . "/panel/pi.php'>PI Management</a>";
      }
      echo "<a target='_blank' href='/panel/jhub'>JupyterLab</a>";
    } else {
      echo "<a href='/panel'>Login / Request Account</a>";
    }
    ?>
  </nav>

  <div class="modalWrapper" style="display: none;">
    <div class="modalContent">
      <div class="modalTitleWrapper">
        <span class="modalTitle">Test Modal</span>
        <button style="position: absolute; right: 10px; top: 10px;" class="btnClose"></button>
      </div>
      <div class="modalBody"></div>
      <div class="modalMessages"></div>
    </div>
  </div>
  <script src="<?php echo config::PREFIX; ?>/js/modal.js"></script>

  <main>