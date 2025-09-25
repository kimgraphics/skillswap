<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

$functions = new Functions();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$currentUser = $functions->getUserById($user_id);

// Default feedback messages
$message = '';
$error = '';

// ✅ Ensure we always have clean defaults
$currentUser['location'] = $currentUser['location'] ?? '';
$currentUser['availability'] = $currentUser['availability'] ?? '';
$currentUser['bio'] = $currentUser['bio'] ?? '';
$currentUser['email'] = $currentUser['email'] ?? '';
$currentUser['username'] = $currentUser['username'] ?? '';

// Fetch all predefined skills
$allSkills = $functions->getAllSkills();

// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $location = trim($_POST['location'] ?? '');
    $availability = trim($_POST['availability'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($functions->updateUserProfile($user_id, $location, $availability, $bio)) {
        $message = "✅ Profile updated successfully!";
        $currentUser = $functions->getUserById($user_id); // refresh user data
    } else {
        $error = "❌ Failed to update profile.";
    }
}

// ✅ Handle add skill form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skill_id = $_POST['skill_id'] ?? '';
    $new_skill = trim($_POST['new_skill'] ?? '');
    $type = $_POST['type'] ?? '';
    $proficiency = $_POST['proficiency'] ?? 'intermediate';
    $description = trim($_POST['description'] ?? '');

    if ($type) {
        if ($skill_id === '' && $new_skill !== '') {
            // Add new custom skill
            $skill_id = $functions->addCustomSkill($new_skill);
        }

        if ($skill_id && $functions->addUserSkill($user_id, $skill_id, $type, $proficiency, $description)) {
            $message = "✅ Skill added successfully!";
        } else {
            $error = "❌ Failed to add skill. Please try again.";
        }
    }
}

// ✅ Handle remove skill
if (isset($_GET['remove_skill'])) {
    $skill_id = intval($_GET['remove_skill']);
    if ($functions->removeUserSkill($user_id, $skill_id)) {
        $message = "✅ Skill removed successfully!";
    } else {
        $error = "❌ Failed to remove skill.";
    }
}

// ✅ Fetch user skills and split by type
$userSkills = $functions->getUserSkills($user_id);
$teachSkills = array_filter($userSkills, fn($s) => $s['type'] === 'teach');
$learnSkills = array_filter($userSkills, fn($s) => $s['type'] === 'learn');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SkillSwap</title>
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
                    <i class="fas fa-exchange-alt me-2"></i> SkillSwap
                </a>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="../assets/images/avatars/<?php echo htmlspecialchars($currentUser['profile_image'] ?? 'default.png'); ?>" 
                                 alt="Profile" class="rounded-circle me-2" width="32" height="32">
                            <?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>
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
                            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="matches.php"><i class="fas fa-handshake me-2"></i>Find Matches</a></li>
                            <li class="nav-item"><a class="nav-link" href="messages.php"><i class="fas fa-comments me-2"></i>Messages</a></li>
                            <li class="nav-item"><a class="nav-link" href="reviews.php"><i class="fas fa-star me-2"></i>Reviews</a></li>
                            <li class="nav-item"><a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <?php if ($isAdmin): ?>
                            <li class="nav-item"><a class="nav-link text-danger" href="../admin/dashboard.php"><i class="fas fa-shield-alt me-2"></i>Admin Panel</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-10 main-content">
                    <div class="container-fluid py-4">
                        <h2 class="mb-4">My Profile</h2>

                        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                        <div class="row">
                            <!-- Profile Information -->
                            <div class="col-md-5">
                                <div class="card mb-4">
                                    <div class="card-header"><h5 class="mb-0">Profile Information</h5></div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled></div>
                                            <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled></div>
                                            <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>" disabled></div>
                                            <div class="mb-3"><label for="location" class="form-label">Location</label><input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($currentUser['location']); ?>"></div>
                                            <div class="mb-3">
                                                <label for="availability" class="form-label">Availability</label>
                                                <select class="form-select" id="availability" name="availability">
                                                    <option value="flexible" <?php echo ($currentUser['availability'] === 'flexible') ? 'selected' : ''; ?>>Flexible</option>
                                                    <option value="weekdays" <?php echo ($currentUser['availability'] === 'weekdays') ? 'selected' : ''; ?>>Weekdays</option>
                                                    <option value="weekends" <?php echo ($currentUser['availability'] === 'weekends') ? 'selected' : ''; ?>>Weekends</option>
                                                    <option value="both" <?php echo ($currentUser['availability'] === 'both') ? 'selected' : ''; ?>>Both Weekdays & Weekends</option>
                                                </select>
                                            </div>
<?php 
$bio = isset($currentUser['bio']) && $currentUser['bio'] !== null 
    ? $currentUser['bio'] 
    : '';
?>
<div class="mb-3">
    <label for="bio" class="form-label">Bio</label>
    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></textarea>
</div>
                                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Skills -->
                            <div class="col-md-7">
                                <!-- Add Skill Form -->
                                <div class="card mb-4">
                                    <div class="card-header"><h5 class="mb-0">Add New Skill</h5></div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <div class="mb-3">
                                                        <label class="form-label">Skill</label>
                                                        <select class="form-select mb-2" name="skill_id">
                                                            <option value="">Select an existing skill</option>
                                                            <?php foreach ($allSkills as $skill): ?>
                                                            <option value="<?php echo $skill['id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="text" class="form-control" name="new_skill" placeholder="Or type a new skill">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="type" class="form-label">Type</label>
                                                        <select class="form-select" id="type" name="type" required>
                                                            <option value="">Select type</option>
                                                            <option value="teach">I can teach this</option>
                                                            <option value="learn">I want to learn this</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="proficiency" class="form-label">Proficiency</label>
                                                        <select class="form-select" id="proficiency" name="proficiency">
                                                            <option value="beginner">Beginner</option>
                                                            <option value="intermediate" selected>Intermediate</option>
                                                            <option value="advanced">Advanced</option>
                                                            <option value="expert">Expert</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description (Optional)</label>
                                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                            </div>
                                            <button type="submit" name="add_skill" class="btn btn-success">Add Skill</button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Skills I Can Teach -->
                                <div class="card mb-4">
                                    <div class="card-header"><h5 class="mb-0">Skills I Can Teach</h5></div>
                                    <div class="card-body">
                                        <?php if ($teachSkills): ?>
                                        <div class="list-group">
                                            <?php foreach ($teachSkills as $skill): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($skill['name']); ?></h6>
                                                    <small class="text-muted"><?php echo ucfirst($skill['proficiency']); ?> level</small>
                                                    <?php if (!empty($skill['description'])): ?><p class="mb-0 mt-1"><?php echo htmlspecialchars($skill['description']); ?></p><?php endif; ?>
                                                </div>
                                                <a href="?remove_skill=<?php echo $skill['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this skill?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?><p class="text-muted">No teaching skills added yet.</p><?php endif; ?>
                                    </div>
                                </div>

                                <!-- Skills I Want to Learn -->
                                <div class="card">
                                    <div class="card-header"><h5 class="mb-0">Skills I Want to Learn</h5></div>
                                    <div class="card-body">
                                        <?php if ($learnSkills): ?>
                                        <div class="list-group">
                                            <?php foreach ($learnSkills as $skill): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($skill['name']); ?></h6>
                                                    <small class="text-muted"><?php echo ucfirst($skill['proficiency']); ?> level desired</small>
                                                    <?php if (!empty($skill['description'])): ?><p class="mb-0 mt-1"><?php echo htmlspecialchars($skill['description']); ?></p><?php endif; ?>
                                                </div>
                                                <a href="?remove_skill=<?php echo $skill['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this skill?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?><p class="text-muted">No learning goals added yet.</p><?php endif; ?>
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
