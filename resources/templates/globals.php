<?php

// Useful functions that don't belong in any particular class
function redirect($destination)
{
  if ($_SERVER["PHP_SELF"] != $destination) {
    header("Location: $destination");
    die("Redirect failed, click <a href='$destination'>here</a> to continue.");
  }
}

function printMessages(&$errors, $str_success)
{
  if (isset($errors)) {
    echo "<div class='message'>";
    if (empty($errors)) {
      echo "<span class='message-success'>$str_success</span>";  // Success Message
    } else {
      foreach ($errors as $err) {
        echo "<span class='message-failure'>$err</span>";
      }
    }
    echo "</div>";
  }
}