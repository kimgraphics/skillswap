<?php
// This file is included at the top of every page
// It handles authentication, common variables, and notifications

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ----------------------------
// Auto logout after inactivity
// ----------------------------
$timeout_duration = 180; // 3 minutes in seconds

if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity']) > $timeout_duration) {
    
    // Destroy session and redirect to login
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

// ----------------------------
// Normal app logic
// ----------------------------
$auth = new Auth();
$functions = new Functions();

// Check if user is logged in
$isLoggedIn = $auth->isLoggedIn();
$isAdmin = $auth->isAdmin();

// Get current user data if logged in
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;

// Get notifications count if user is logged in
$unreadNotifications = $isLoggedIn
    ? $functions->getUnreadNotificationsCount($_SESSION['user_id'])
    : 0;
?>

<!-- JavaScript auto-logout after inactivity -->
<script>
(function() {
    let inactivityTime = 180000; // 3 minutes in milliseconds
    let timeout;

    function resetTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(logoutUser, inactivityTime);
    }

    function logoutUser() {
        // Redirect to logout page
        window.location.href = "logout.php?timeout=1";
    }

    // Detect activity (mouse, keyboard, scroll, touch)
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeydown = resetTimer;
    document.onscroll = resetTimer;
    document.ontouchstart = resetTimer;
})();
</script>
