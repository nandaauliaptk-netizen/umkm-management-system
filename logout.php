<?php
session_start();
$_SESSION = [];
session_destroy();
session_start();
$_SESSION['flash_logout'] = true;
header('Location: login.php');
exit();