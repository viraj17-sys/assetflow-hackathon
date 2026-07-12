<?php
require_once 'config/session.php';
require_once 'config/auth.php';

logoutUser();
header('Location: login.php');
exit;
?>