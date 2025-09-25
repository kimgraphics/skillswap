<?php
require_once '../includes/header.php';

// QUICK fallback: ensure $conn exists (won't re-include if header already created it)
if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../includes/database.php';
    $database = new Database();
    $conn = $database->getConnection();
}


// Get all skills
$stmt = $conn->prepare("SELECT * FROM skills ORDER BY name");
$stmt->execute();
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Handle skill actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_skill'])) {
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO skills (name, category, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $category, $description])) {
                $message = 'Skill added successfully!';
                // Refresh skills list
                $stmt = $conn->prepare("SELECT * FROM skills ORDER BY name");
                $stmt->execute();
                $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to add skill. It may already exist.';
            }
        } else {
            $error = 'Skill name is required.';
        }
    }
    
    if (isset($_POST['edit_skill'])) {
        $skill_id = $_POST['skill_id'];
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        
        if (!empty($name)) {
            $stmt = $conn->prepare("UPDATE skills SET name = ?, category = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $category, $description, $skill_id])) {
                $message = 'Skill updated successfully!';
                // Refresh skills list
                $stmt = $conn->prepare("SELECT * FROM skills ORDER BY name");
                $stmt->execute();
                $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = 'Failed to update skill.';
            }
        } else {
            $error = 'Skill name is required.';
        }
    }
    
    if (isset($_POST['delete_skill'])) {
        $skill_id = $_POST['skill_id'];
        
        $stmt = $conn->prepare("DELETE FROM skills WHERE id = ?");
        if ($stmt->execute([$skill_id])) {
            $message = 'Skill deleted successfully!';
            // Refresh skills list
            $stmt = $conn->prepare("SELECT * FROM skills ORDER BY name");
            $stmt->execute();
            $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to delete skill. It may be in use by users.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Skills - SkillSwap Admin</title>
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
                                <a class="nav-link active" href="skills.php">
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
                            <h2>Manage Skills</h2>
                            <span class="badge bg-primary">Total: <?php echo count($skills); ?> skills</span>
                        </div>

                        <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Add Skill Form -->
                            <div class="col-md-5">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Add New Skill</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Skill Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="category" class="form-label">Category</label>
                                                <input type="text" class="form-control" id="category" name="category" placeholder="e.g., Technology, Creative, Lifestyle">
                                            </div>
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>
                                            <button type="submit" name="add_skill" class="btn btn-primary">Add Skill</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Skills List -->
                            <div class="col-md-7">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">All Skills</h5>
                                        <span class="badge bg-secondary"><?php echo count($skills); ?> skills</span>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($skills) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($skills as $skill): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($skill['name']); ?></strong>
                                                            <?php if (!empty($skill['description'])): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($skill['description']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo !empty($skill['category']) ? htmlspecialchars($skill['category']) : '—'; ?></td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSkillModal<?php echo $skill['id']; ?>">
                                                                    Edit
                                                                </button>
                                                                <form method="POST" action="" class="d-inline">
                                                                    <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                                                                    <button type="submit" name="delete_skill" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this skill?')">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </div>

                                                            <!-- Edit Skill Modal -->
                                                            <div class="modal fade" id="editSkillModal<?php echo $skill['id']; ?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Edit Skill</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <form method="POST" action="">
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                                                                                <div class="mb-3">
                                                                                    <label for="edit_name<?php echo $skill['id']; ?>" class="form-label">Skill Name *</label>
                                                                                    <input type="text" class="form-control" id="edit_name<?php echo $skill['id']; ?>" name="name" value="<?php echo htmlspecialchars($skill['name']); ?>" required>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="edit_category<?php echo $skill['id']; ?>" class="form-label">Category</label>
                                                                                    <input type="text" class="form-control" id="edit_category<?php echo $skill['id']; ?>" name="category" value="<?php echo htmlspecialchars($skill['category']); ?>">
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label for="edit_description<?php echo $skill['id']; ?>" class="form-label">Description</label>
                                                                                    <textarea class="form-control" id="edit_description<?php echo $skill['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($skill['description']); ?></textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" name="edit_skill" class="btn btn-primary">Save Changes</button>
                                                                            </div>
                                                                        </form>
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
                                        <p class="text-muted">No skills found. Add some skills to get started.</p>
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
</body>
</html>