<?php
require_once '../includes/header.php';

// Ensure user is logged in and is an admin
if (!$isLoggedIn || !$isAdmin) {
    header('Location: login.php');
    exit;
}

// Ensure $conn is defined
if (!isset($conn) || !($conn instanceof PDO)) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Get analytics data
try {
    // User growth data (last 6 months)
    $userGrowth = [];
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime($month . '-01'));

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND role = 'user'");
        $stmt->execute([$month]);
        $userGrowth[] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    // Skill popularity data
    $stmt = $conn->prepare("
        SELECT s.name, COUNT(us.skill_id) as count 
        FROM user_skills us 
        JOIN skills s ON us.skill_id = s.id 
        WHERE us.type = 'teach' 
        GROUP BY us.skill_id 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $skillData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $skillNames = [];
    $skillCounts = [];
    foreach ($skillData as $skill) {
        $skillNames[] = $skill['name'];
        $skillCounts[] = $skill['count'];
    }

    // Match status distribution
    $matchStatus = [];
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM matches GROUP BY status");
    $stmt->execute();
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($statusData as $status) {
        $matchStatus[$status['status']] = $status['count'];
    }

    // Platform stats
    $stats = [
        'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
        'active_users' => $conn->query("SELECT COUNT(*) FROM users WHERE is_active = TRUE AND role = 'user'")->fetchColumn(),
        'total_matches' => $conn->query("SELECT COUNT(*) FROM matches")->fetchColumn(),
        'completed_matches' => $conn->query("SELECT COUNT(*) FROM matches WHERE status = 'completed'")->fetchColumn(),
        'total_messages' => $conn->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
        'total_reviews' => $conn->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
        'avg_rating' => round((float)($conn->query("SELECT AVG(rating) FROM reviews")->fetchColumn() ?? 0), 1)
    ];

    // Top users by matches
    $topUsers = $conn->query("
        SELECT u.username, u.first_name, u.last_name, COUNT(m.id) as match_count
        FROM users u
        LEFT JOIN matches m ON (u.id = m.user1_id OR u.id = m.user2_id)
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY match_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Failed to fetch analytics data: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - SkillSwap Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-shield-alt me-2"></i>
                    SkillSwap <span class="badge bg-warning">Admin</span>
                </a>
                
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">
                        <i class="fas fa-user-shield me-1"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../pages/dashboard.php">
                                <i class="fas fa-user me-2"></i>User View
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../includes/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-2 bg-dark sidebar">
                    <div class="sidebar-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="users.php">
                                    <i class="fas fa-users me-2"></i>
                                    Manage Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="skills.php">
                                    <i class="fas fa-tags me-2"></i>
                                    Manage Skills
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="matches.php">
                                    <i class="fas fa-handshake me-2"></i>
                                    View Matches
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="analytics.php">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Analytics
                                </a>
                            </li>
                            <li class="nav-item mt-4">
                                <a class="nav-link text-warning" href="../pages/dashboard.php">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to User Site
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-10 main-content">
                    <div class="container-fluid py-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Platform Analytics</h2>
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print Report
                            </button>
                        </div>

                        <!-- Stats Overview -->
                        <div class="row mb-4">
                            <div class="col-md-2 col-6 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-primary"><?php echo $stats['total_users']; ?></h3>
                                        <small class="text-muted">Total Users</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-success"><?php echo $stats['active_users']; ?></h3>
                                        <small class="text-muted">Active Users</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-info"><?php echo $stats['total_matches']; ?></h3>
                                        <small class="text-muted">Total Matches</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-warning"><?php echo $stats['completed_matches']; ?></h3>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-danger"><?php echo $stats['total_messages']; ?></h3>
                                        <small class="text-muted">Messages</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h3 class="text-purple"><?php echo $stats['avg_rating']; ?>/5</h3>
                                        <small class="text-muted">Avg Rating</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- User Growth Chart -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">User Growth (Last 6 Months)</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="userGrowthChart" height="250"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Skill Popularity Chart -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Most Popular Skills</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="skillPopularityChart" height="250"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Match Status Chart -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Match Status Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="matchStatusChart" height="250"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Users -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Top Users by Matches</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($topUsers) > 0): ?>
                                        <div class="list-group">
                                            <?php foreach ($topUsers as $index => $user): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                                <span class="badge bg-success"><?php echo $user['match_count']; ?> matches</span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted">No user data available.</p>
                                        <?php endif; ?>
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
    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode($userGrowth); ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Skill Popularity Chart
        const skillPopularityCtx = document.getElementById('skillPopularityChart').getContext('2d');
        new Chart(skillPopularityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($skillNames); ?>,
                datasets: [{
                    label: 'Number of Users',
                    data: <?php echo json_encode($skillCounts); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Match Status Chart
        const matchStatusCtx = document.getElementById('matchStatusChart').getContext('2d');
        new Chart(matchStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Accepted', 'Rejected', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $matchStatus['pending'] ?? 0; ?>,
                        <?php echo $matchStatus['accepted'] ?? 0; ?>,
                        <?php echo $matchStatus['rejected'] ?? 0; ?>,
                        <?php echo $matchStatus['completed'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(6, 182, 212, 0.8)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>