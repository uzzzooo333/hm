<?php
session_start();
if (isset($_SESSION['user'])) {
    require_once 'config.php';
    log_activity($_SESSION['user']['id'], 'logout', 'User logged out');
}
session_destroy();
header('Location: index.php');
exit;
?>
