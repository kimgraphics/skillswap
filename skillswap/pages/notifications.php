<?php
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$functions = new Functions();
$notifications = $functions->getNotifications($_SESSION['user_id']);

// Mark all as read only if the user clicks the button
if (isset($_GET['mark_all_read']) && !empty($notifications)) {
    $functions->markAllNotificationsAsRead($_SESSION['user_id']);
    header("Location: notifications.php"); // Refresh so badge updates
    exit;
}

// Get updated counts
$unreadNotifications = $functions->getUnreadNotificationsCount($_SESSION['user_id']);
$totalUnread = $functions->getTotalUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fa-solid fa-right-left me-2"></i>
                    SkillSwap
                </a>
                
                <div class="d-flex align-items-center">
                    <!-- Notifications -->
                    <?php if ($isLoggedIn): ?>
                    <div class="dropdown me-3">
                        <a href="notifications.php" class="btn btn-light position-relative">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadNotifications; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- User Profile -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="../assets/images/avatars/<?php echo $currentUser['profile_image'] ?: 'default.png'; ?>" 
                                 alt="Profile" class="rounded-circle me-2" width="32" height="32">
                            <?php echo htmlspecialchars($currentUser['first_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">View Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../includes/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-2 bg-light sidebar">
                    <div class="sidebar-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fa-solid fa-home me-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="profile.php">
                                    <i class="fa-solid fa-user me-2"></i>
                                    My Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="matches.php">
                                    <i class="fa-solid fa-handshake me-2"></i>
                                    Find Matches
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="messages.php">
                                    <i class="fa-solid fa-comments me-2"></i>
                                    Messages
                                    <?php if ($totalUnread > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $totalUnread; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="notifications.php">
                                    <i class="fa-solid fa-bell me-2"></i>
                                    Notifications
                                    <?php if ($unreadNotifications > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $unreadNotifications; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reviews.php">
                                    <i class="fa-solid fa-star me-2"></i>
                                    Reviews
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="settings.php">
                                    <i class="fa-solid fa-cog me-2"></i>
                                    Settings
                                </a>
                            </li>
                            <?php if ($isAdmin): ?>
                            <li class="nav-item">
                                <a class="nav-link text-danger" href="../admin/dashboard.php">
                                    <i class="fa-solid fa-shield-halved me-2"></i>
                                    Admin Panel
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-10 main-content">
                    <div class="container-fluid py-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Notifications</h2>
                            <?php if (!empty($notifications)): ?>
                            <form method="GET" action="notifications.php">
                                <input type="hidden" name="mark_all_read" value="1">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-check-double me-1"></i> Mark all as read
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <?php if (!empty($notifications)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i a', strtotime($notification['created_at'])); ?>
                                                    <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary ms-2">New</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <?php if ($notification['type'] == 'message' && $notification['related_id']): ?>
                                            <a href="messages.php?match_id=<?php echo $notification['related_id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                                <i class="fas fa-comment"></i> View
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                    <h5>No notifications yet</h5>
                                    <p class="text-muted">You'll see notifications here when you have new matches or messages.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>