<?php
session_start();

// Destroy cookie
setcookie("auth_token", "", time() - 3600, "/");

// Destroy session
session_unset();
session_destroy();

header("Location: login.php");
exit();