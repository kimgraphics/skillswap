<?php
require_once '../includes/header.php';
require_once '../includes/database.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

if (!$isLoggedIn || !$isAdmin) {
    header('Location: login.php');
    exit;
}

// Fetch current admin user for navbar display
$currentUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$currentUser->execute([$_SESSION['user_id']]);
$currentUser = $currentUser->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;

    if ($user_id) {
        if (isset($_POST['toggle_user_status'])) {
            $is_active = $_POST['is_active'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$is_active, $user_id])) {
                $message = 'User status updated successfully!';
            } else {
                $error = 'Failed to update user status. Please try again.';
            }
        } elseif (isset($_POST['delete_user'])) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            if ($stmt->execute([$user_id])) {
                $message = 'User deleted successfully!';
            } else {
                $error = 'Failed to delete user. Cannot delete admin users.';
            }
        } elseif (isset($_POST['make_admin'])) {
            $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'User promoted to admin successfully!';
            } else {
                $error = 'Failed to promote user. Please try again.';
            }
        }
    }
}

// Fetch all users once
$stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch analytics data for dashboard

// User growth last 6 months
$userGrowth = [];
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($month . '-01'));

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND role = 'user'");
    $stmt->execute([$month]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    $userGrowth[] = (int)$count;
}

// Skill popularity
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
    $skillCounts[] = (int)($skill['count'] ?? 0);
}

// Match status distribution
$matchStatus = [];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM matches GROUP BY status");
$stmt->execute();
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($statusData as $status) {
    $matchStatus[$status['status']] = (int)($status['count'] ?? 0);
}

// Platform stats
$stats = [
    'total_users' => (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'active_users' => (int)$conn->query("SELECT COUNT(*) FROM users WHERE is_active = TRUE AND role = 'user'")->fetchColumn(),
    'total_matches' => (int)$conn->query("SELECT COUNT(*) FROM matches")->fetchColumn(),
    'completed_matches' => (int)$conn->query("SELECT COUNT(*) FROM matches WHERE status = 'completed'")->fetchColumn(),
    'total_messages' => (int)$conn->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'total_reviews' => (int)$conn->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SkillSwap Admin</title>
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
                                <a class="nav-link active" href="users.php">
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
                            <h2>Manage Users</h2>
                            <span class="badge bg-primary">Total: <?php echo count($users); ?> users</span>
                        </div>

                        <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">All Users</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Joined</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../assets/images/avatars/<?php echo $user['profile_image']; ?>" 
                                                             alt="Profile" class="rounded-circle me-2" width="32" height="32">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($user['role'] !== 'admin'): ?>
                                                        <form method="POST" class="me-1">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="is_active" value="<?php echo $user['is_active']; ?>">
                                                            <button type="submit" name="toggle_user_status" class="btn btn-sm btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>">
                                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" class="me-1">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="make_admin" class="btn btn-sm btn-info">
                                                                Make Admin
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                                Delete
                                                            </button>
                                                        </form>
                                                        <?php else: ?>
                                                        <span class="text-muted">Admin actions disabled</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="">All Roles</option>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>