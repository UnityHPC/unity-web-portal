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

function EPPN_to_uid($eppn) {
  $eppn_output = str_replace(".", "_", $eppn);
  $eppn_output = str_replace("@", "_", $eppn_output);
  return strtolower($eppn_output);
}

function getGithubKeys($username) {
  $url = "https://api.github.com/users/$username/keys";
  $headers = array(
    "User-Agent: Unity Cluster User Portal"
  );

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  $output = json_decode(curl_exec($curl), true);
  curl_close($curl);

  $out = array();
  foreach ($output as $value) {
    array_push($out, $value["key"]);
  }

  return $out;
}

function removeTrailingWhitespace($arr) {
  $out = array();
  foreach ($arr as $str) {
    $new_string = rtrim($str);
    array_push($out, $new_string);
  }

  return $out;
}
