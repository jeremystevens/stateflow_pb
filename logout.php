<?php
// check if the session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to homepage or login page
header("Location: index.php"); // or wherever you want to redirect
exit;
?>
