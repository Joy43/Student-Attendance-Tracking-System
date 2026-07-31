<?php
// Start the session to access session variables
session_start();

// Check if faculty is logged in
if (isset($_SESSION['faculty'])) {
    // Remove all session data
    session_unset();
    session_destroy();

    // Redirect to login page after logout
    header("Location: /attapp/login/index.php");
    exit();
} else {
    // If no faculty session found, redirect directly to login
    header("Location: /attapp/login/index.php");
    exit();
}
?>
