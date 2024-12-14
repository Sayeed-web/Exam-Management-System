<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect to role-specific dashboard
switch($_SESSION['role']) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'instructor':
        header('Location: instructor/dashboard.php');
        break;
    case 'student':
        header('Location: student/dashboard.php');
        break;
}
exit;
?>
