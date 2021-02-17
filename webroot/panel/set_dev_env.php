<?php

//access this file to access maintenance mode
session_start();
$_SESSION["maint"] = true;