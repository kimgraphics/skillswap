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

// Get all matches with user details
try {
    $stmt = $conn->prepare("
        SELECT m.*, 
               u1.username as user1_username, u1.first_name as user1_first_name, u1.last_name as user1_last_name,
               u2.username as user2_username, u2.first_name as user2_first_name, u2.last_name as user2_last_name,
               s_teach.name as teach_skill, s_learn.name as learn_skill
        FROM matches m
        JOIN users u1 ON m.user1_id = u1.id
        JOIN users u2 ON m.user2_id = u2.id
        JOIN skills s_teach ON m.skill_teach_id = s_teach.id
        JOIN skills s_learn ON m.skill_learn_id = s_learn.id
        ORDER BY m.matched_at DESC
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Failed to fetch matches: " . $e->getMessage());
}

// Get match statistics
$total_matches = count($matches);
$pending_matches = array_filter($matches, fn($match) => $match['status'] === 'pending');
$accepted_matches = array_filter($matches, fn($match) => $match['status'] === 'accepted');
$completed_matches = array_filter($matches, fn($match) => $match['status'] === 'completed');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Matches - SkillSwap Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
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
                                <a class="nav-link active" href="matches.php">
                                    <i class="fas fa-handshake me-2"></i>
                                    View Matches
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="analytics.php">
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
                            <h2>View Matches</h2>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary btn-sm active">All (<?php echo $total_matches; ?>)</button>
                                <button class="btn btn-outline-warning btn-sm">Pending (<?php echo count($pending_matches); ?>)</button>
                                <button class="btn btn-outline-success btn-sm">Accepted (<?php echo count($accepted_matches); ?>)</button>
                                <button class="btn btn-outline-info btn-sm">Completed (<?php echo count($completed_matches); ?>)</button>
                            </div>
                        </div>

                        <!-- Match Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo $total_matches; ?></h4>
                                                <span>Total Matches</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-handshake fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo count($pending_matches); ?></h4>
                                                <span>Pending</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo count($accepted_matches); ?></h4>
                                                <span>Accepted</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo count($completed_matches); ?></h4>
                                                <span>Completed</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-flag-checkered fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">All Matches</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($matches) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Match ID</th>
                                                <th>Users</th>
                                                <th>Skills Exchange</th>
                                                <th>Status</th>
                                                <th>Matched Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($matches as $match): ?>
                                            <tr>
                                                <td>#<?php echo $match['id']; ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <strong><?php echo htmlspecialchars($match['user1_first_name'] . ' ' . $match['user1_last_name']); ?></strong>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($match['user1_username']); ?></small>
                                                        <span class="text-primary">↔</span>
                                                        <strong><?php echo htmlspecialchars($match['user2_first_name'] . ' ' . $match['user2_last_name']); ?></strong>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($match['user2_username']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-success"><i class="fas fa-arrow-up me-1"></i> <?php echo htmlspecialchars($match['teach_skill']); ?></span>
                                                        <span class="text-primary"><i class="fas fa-arrow-down me-1"></i> <?php echo htmlspecialchars($match['learn_skill']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $match['status'] == 'accepted' ? 'success' : 
                                                             ($match['status'] == 'pending' ? 'warning' : 
                                                             ($match['status'] == 'completed' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($match['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($match['matched_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#matchDetailsModal<?php echo $match['id']; ?>">
                                                            Details
                                                        </button>
                                                        <?php if ($match['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Match Details Modal -->
                                                    <div class="modal fade" id="matchDetailsModal<?php echo $match['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Match Details #<?php echo $match['id']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <h6>User 1</h6>
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($match['user1_first_name'] . ' ' . $match['user1_last_name']); ?></p>
                                                                            <p><strong>Username:</strong> @<?php echo htmlspecialchars($match['user1_username']); ?></p>
                                                                            <p><strong>Teaching:</strong> <?php echo htmlspecialchars($match['teach_skill']); ?></p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6>User 2</h6>
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($match['user2_first_name'] . ' ' . $match['user2_last_name']); ?></p>
                                                                            <p><strong>Username:</strong> @<?php echo htmlspecialchars($match['user2_username']); ?></p>
                                                                            <p><strong>Teaching:</strong> <?php echo htmlspecialchars($match['learn_skill']); ?></p>
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <p><strong>Status:</strong> <span class="badge bg-<?php echo $match['status'] == 'accepted' ? 'success' : 'warning'; ?>"><?php echo ucfirst($match['status']); ?></span></p>
                                                                            <p><strong>Matched On:</strong> <?php echo date('F j, Y g:i A', strtotime($match['matched_at'])); ?></p>
                                                                            <?php if ($match['completed_at']): ?>
                                                                            <p><strong>Completed On:</strong> <?php echo date('F j, Y g:i A', strtotime($match['completed_at'])); ?></p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                                    <h5>No matches found</h5>
                                    <p class="text-muted">There are no matches in the system yet.</p>
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