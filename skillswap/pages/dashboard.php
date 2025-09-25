<?php
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$functions = new Functions();
$userStats = $functions->getUserStats($_SESSION['user_id']);
$recentMatches = $functions->getUserMatches($_SESSION['user_id']);
// Limit to 3 recent matches
$recentMatches = array_slice($recentMatches, 0, 3);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SkillSwap</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome (CDN, guaranteed to work) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <!-- Dashboard CSS -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-exchange-alt me-2"></i>
                    SkillSwap
                </a>
                
                <div class="d-flex align-items-center">
                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <button class="btn btn-light position-relative" type="button" id="notificationDropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($userStats['unread_messages'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $userStats['unread_messages']; ?>
                            </span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <!-- User Profile -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="../assets/images/avatars/<?php echo $currentUser['profile_image']; ?>" 
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
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-home me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user me-2"></i>
                    My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="matches.php">
                    <i class="fas fa-handshake me-2"></i>
                    Find Matches
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="messages.php">
                    <i class="fas fa-comments me-2"></i>
                    Messages
                    <?php if ($userStats['unread_messages'] > 0): ?>
                    <span class="badge bg-primary ms-2"><?php echo $userStats['unread_messages']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reviews.php">
                    <i class="fas fa-star me-2"></i>
                    Reviews
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li class="nav-item">
                <a class="nav-link text-danger" href="../admin/login.php">
                    <i class="fas fa-shield-alt me-2"></i>
                    Admin Panel
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>


                <!-- Main Content -->
                <div class="col-lg-10 main-content">
                    <div class="container-fluid py-4">
                        <!-- Welcome Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="welcome-card bg-gradient-primary text-white p-4 rounded-3">
                                    <h2>Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>! 👋</h2>
                                    <p class="mb-0">Ready to continue your skill exchange journey?</p>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Overview -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-primary">
                                                <i class="fas fa-handshake text-white"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="card-title mb-0">Total Matches</h6>
                                                <h3 class="mb-0"><?php echo $userStats['total_matches']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success">
                                                <i class="fas fa-comments text-white"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="card-title mb-0">Unread Messages</h6>
                                                <h3 class="mb-0"><?php echo $userStats['unread_messages']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-info">
                                                <i class="fas fa-star text-white"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="card-title mb-0">Your Rating</h6>
                                                <h3 class="mb-0"><?php echo $userStats['avg_rating'] ?: 'N/A'; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-warning">
                                                <i class="fas fa-exchange-alt text-white"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="card-title mb-0">Skills Offered</h6>
                                                <h3 class="mb-0"><?php echo $userStats['skills_offered']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity & Quick Actions -->
                        <div class="row">
                            <!-- Recent Matches -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Recent Matches</h5>
                                        <a href="matches.php" class="btn btn-sm btn-outline-primary">View All</a>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($recentMatches) > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($recentMatches as $match): 
                                                $otherUser = ($match['user1_id'] == $_SESSION['user_id']) ? 
                                                    ['name' => $match['user2_first_name'] . ' ' . $match['user2_last_name'], 'username' => $match['user2_username']] :
                                                    ['name' => $match['user1_first_name'] . ' ' . $match['user1_last_name'], 'username' => $match['user1_username']];
                                            ?>
                                            <div class="list-group-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($otherUser['name']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($match['teach_skill']); ?> for <?php echo htmlspecialchars($match['learn_skill']); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-<?php 
                                                        echo $match['status'] == 'accepted' ? 'success' : 
                                                             ($match['status'] == 'pending' ? 'warning' : 
                                                             ($match['status'] == 'completed' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($match['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted">No matches yet. <a href="matches.php">Find your first match!</a></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0">Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <a href="profile.php" class="btn btn-outline-primary w-100 h-100 py-3">
                                                    <i class="fas fa-user-edit fa-2x mb-2"></i>
                                                    <br>
                                                    Edit Profile
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="matches.php" class="btn btn-outline-success w-100 h-100 py-3">
                                                    <i class="fas fa-search fa-2x mb-2"></i>
                                                    <br>
                                                    Find Matches
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="messages.php" class="btn btn-outline-info w-100 h-100 py-3">
                                                    <i class="fas fa-comments fa-2x mb-2"></i>
                                                    <br>
                                                    Messages
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="settings.php" class="btn btn-outline-secondary w-100 h-100 py-3">
                                                    <i class="fas fa-cog fa-2x mb-2"></i>
                                                    <br>
                                                    Settings
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>