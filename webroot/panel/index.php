<?php

require "../../resources/autoload.php";

use UnityWebPortal\lib\UnitySite;

UnitySite::redirect($CONFIG["site"]["prefix"] . "/panel/account.php");
