<?php
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$functions = new Functions();
$potentialMatches = $functions->findMatches($_SESSION['user_id']);
$userMatches = $functions->getUserMatches($_SESSION['user_id']);

$message = '';
$error = '';

// Handle match status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_match_status'])) {
    $match_id = $_POST['match_id'];
    $status = $_POST['status'];
    
    if ($functions->updateMatchStatus($match_id, $status, $_SESSION['user_id'])) {
        $message = 'Match ' . $status . ' successfully!';
        
        // Create notification for the other user
        $match = $functions->getMatchDetails($match_id);
        $other_user_id = ($match['user1_id'] == $_SESSION['user_id']) ? $match['user2_id'] : $match['user1_id'];
        
        $functions->createNotification(
            $other_user_id,
            'match',
            'Match ' . ucfirst($status),
            $_SESSION['first_name'] . ' has ' . $status . ' your skill exchange proposal',
            $match_id
        );
        
        // Refresh matches
        $userMatches = $functions->getUserMatches($_SESSION['user_id']);
    } else {
        $error = 'Failed to update match status.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Matches - SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css" rel="stylesheet">
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
                        <a href="messages.php" class="btn btn-light position-relative" id="notificationDropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadNotifications; ?>
                            </span>
                            <?php endif; ?>
                        </a>
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
                                <a class="nav-link" href="dashboard.php">
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
                                <a class="nav-link active" href="matches.php">
                                    <i class="fas fa-handshake me-2"></i>
                                    Find Matches
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="messages.php">
                                    <i class="fas fa-comments me-2"></i>
                                    Messages
                                    <?php if ($unreadNotifications > 0): ?>
                                    <span class="badge bg-primary ms-2"><?php echo $unreadNotifications; ?></span>
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
                                <a class="nav-link text-danger" href="../admin/dashboard.php">
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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Find Matches</h2>
                        </div>

                        <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <!-- Potential Matches -->
                        <div class="card mb-5">
                            <div class="card-header">
                                <h5 class="mb-0">Potential Matches</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($potentialMatches) > 0): ?>
                                <div class="row">
                                    <?php foreach ($potentialMatches as $match): 
                                        $userSkills = $functions->getUserSkills($match['id']);
                                        $teachSkills = array_filter($userSkills, function($skill) {
                                            return $skill['type'] === 'teach';
                                        });
                                        $learnSkills = array_filter($userSkills, function($skill) {
                                            return $skill['type'] === 'learn';
                                        });
                                    ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <img src="../assets/images/avatars/<?php echo $match['profile_image']; ?>" 
                                                         alt="Profile" class="rounded-circle me-3" width="64" height="64">
                                                    <div>
                                                        <h5 class="mb-0"><?php echo htmlspecialchars($match['first_name'] . ' ' . $match['last_name']); ?></h5>
                                                        <p class="text-muted mb-0">@<?php echo htmlspecialchars($match['username']); ?></p>
                                                        <?php if ($match['location']): ?>
                                                        <p class="text-muted mb-0"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($match['location']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <h6>Can Teach:</h6>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($teachSkills as $skill): ?>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($skill['name']); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <h6>Wants to Learn:</h6>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($learnSkills as $skill): ?>
                                                        <span class="badge bg-success"><?php echo htmlspecialchars($skill['name']); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <?php if ($match['bio']): ?>
                                                <div class="mb-3">
                                                    <h6>About:</h6>
                                                    <p class="text-muted"><?php echo htmlspecialchars($match['bio']); ?></p>
                                                </div>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#matchModal<?php echo $match['id']; ?>">
                                                    Propose Skill Exchange
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Match Modal -->
                                    <div class="modal fade" id="matchModal<?php echo $match['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Propose Skill Exchange with <?php echo htmlspecialchars($match['first_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $match['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">I will teach:</label>
                                                            <select class="form-select" name="teach_skill_id" required>
                                                                <option value="">Select a skill to teach</option>
                                                                <?php 
                                                                $myTeachSkills = $functions->getUserSkills($_SESSION['user_id'], 'teach');
                                                                foreach ($myTeachSkills as $skill): 
                                                                ?>
                                                                <option value="<?php echo $skill['skill_id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">In exchange for learning:</label>
                                                            <select class="form-select" name="learn_skill_id" required>
                                                                <option value="">Select a skill to learn</option>
                                                                <?php 
                                                                $myLearnSkills = $functions->getUserSkills($_SESSION['user_id'], 'learn');
                                                                foreach ($myLearnSkills as $skill): 
                                                                ?>
                                                                <option value="<?php echo $skill['skill_id']; ?>"><?php echo htmlspecialchars($skill['name']); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="create_match" class="btn btn-primary">Send Proposal</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No potential matches found. Make sure you've added skills you can teach and want to learn in your profile.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Existing Matches -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">My Matches</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($userMatches) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($userMatches as $match): 
                                        $otherUser = ($match['user1_id'] == $_SESSION['user_id']) ? 
                                            ['id' => $match['user2_id'], 'name' => $match['user2_first_name'] . ' ' . $match['user2_last_name'], 'username' => $match['user2_username']] :
                                            ['id' => $match['user1_id'], 'name' => $match['user1_first_name'] . ' ' . $match['user1_last_name'], 'username' => $match['user1_username']];
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/images/avatars/default.png" 
                                                     alt="Profile" class="rounded-circle me-3" width="48" height="48">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($otherUser['name']); ?></h6>
                                                    <p class="text-muted mb-0">
                                                        <?php echo htmlspecialchars($match['teach_skill']); ?> for <?php echo htmlspecialchars($match['learn_skill']); ?>
                                                    </p>
                                                    <small class="text-muted">Matched on <?php echo date('M j, Y', strtotime($match['matched_at'])); ?></small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-<?php 
                                                    echo $match['status'] == 'accepted' ? 'success' : 
                                                         ($match['status'] == 'pending' ? 'warning' : 
                                                         ($match['status'] == 'completed' ? 'info' : 'secondary')); 
                                                ?> me-2">
                                                    <?php echo ucfirst($match['status']); ?>
                                                </span>
                                                
                                                <?php if ($match['status'] == 'pending' && $match['user2_id'] == $_SESSION['user_id']): ?>
                                                <div class="btn-group me-2">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                                        <input type="hidden" name="status" value="accepted">
                                                        <button type="submit" name="update_match_status" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Accept
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" name="update_match_status" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($match['status'] == 'accepted' || $match['status'] == 'completed'): ?>
                                                <a href="messages.php?match_id=<?php echo $match['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-comment"></i> Message
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">You don't have any matches yet. Find potential matches above and send them a proposal!</p>
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