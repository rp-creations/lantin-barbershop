<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    addLog('Logout', "User logged out");
}

session_destroy();
header('Location: login.php');
exit();