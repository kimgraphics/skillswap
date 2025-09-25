<?php
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$functions = new Functions();
$user_id = $_SESSION['user_id'];
$userMatches = $functions->getUserMatches($user_id);
$completedMatches = array_filter($userMatches, fn($m) => $m['status'] === 'completed');
$userReviews = $functions->getUserReviews($user_id);

$message = '';
$error = '';

// Handle adding a review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $match_id = $_POST['match_id'] ?? '';
    $reviewed_id = $_POST['reviewed_id'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    if ($match_id && $reviewed_id && $rating) {
        if ($functions->addReview($match_id, $user_id, $reviewed_id, $rating, $comment)) {
            $message = 'Review submitted successfully!';
            // Refresh reviews
            $userReviews = $functions->getUserReviews($user_id);
        } else {
            $error = 'Failed to submit review. You may have already reviewed this match.';
        }
    } else {
        $error = 'Please provide a rating.';
    }
}

// Handle marking match as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_match'])) {
    $match_id = $_POST['match_id'] ?? '';
    
    if ($match_id) {
        if ($functions->completeMatch($match_id, $user_id)) {
            $message = 'Match marked as completed. You can now leave a review.';
            $userMatches = $functions->getUserMatches($user_id); // refresh matches
            $completedMatches = array_filter($userMatches, fn($m) => $m['status'] === 'completed');
        } else {
            $error = 'Failed to mark match as completed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="dashboard-container">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-exchange-alt me-2"></i>SkillSwap</a>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <img src="../assets/images/avatars/<?php echo $currentUser['profile_image']; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
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
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="matches.php"><i class="fas fa-handshake me-2"></i>Find Matches</a></li>
                        <li class="nav-item"><a class="nav-link" href="messages.php"><i class="fas fa-comments me-2"></i>Messages</a></li>
                        <li class="nav-item"><a class="nav-link active" href="reviews.php"><i class="fas fa-star me-2"></i>Reviews</a></li>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Reviews & Ratings</h2>
                    </div>

                    <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

                    <div class="row">
                        <!-- Leave a Review -->
                        <div class="col-md-5">
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0">Leave a Review</h5></div>
                                <div class="card-body">
                                    <?php if (count($completedMatches) > 0): ?>
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="match_id" class="form-label">Select Match</label>
                                            <select class="form-select" id="match_id" name="match_id" required>
                                                <option value="">Select a completed match</option>
                                                <?php foreach ($completedMatches as $match): 
                                                    $otherUser = ($match['user1_id'] == $user_id) ? 
                                                        ['id' => $match['user2_id'], 'name' => $match['user2_first_name'].' '.$match['user2_last_name']] :
                                                        ['id' => $match['user1_id'], 'name' => $match['user1_first_name'].' '.$match['user1_last_name']];
                                                ?>
                                                <option value="<?php echo $match['id']; ?>" data-user-id="<?php echo $otherUser['id']; ?>">
                                                    <?php echo htmlspecialchars($otherUser['name']); ?> - <?php echo htmlspecialchars($match['teach_skill']); ?> for <?php echo htmlspecialchars($match['learn_skill']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="reviewed_id" id="reviewed_id">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Rating</label>
                                            <div class="d-flex align-items-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                    <label class="form-check-label" for="rating<?php echo $i; ?>"><?php echo $i; ?> <i class="fas fa-star text-warning"></i></label>
                                                </div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="comment" class="form-label">Comment (Optional)</label>
                                            <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Share your experience..."></textarea>
                                        </div>
                                        <button type="submit" name="add_review" class="btn btn-primary">Submit Review</button>
                                    </form>
                                    <?php else: ?>
                                    <p class="text-muted">No completed matches yet. Complete a skill exchange to leave a review.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- My Reviews -->
                        <div class="col-md-7">
                            <div class="card">
                                <div class="card-header"><h5 class="mb-0">Reviews About Me</h5></div>
                                <div class="card-body">
                                    <?php if (count($userReviews) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($userReviews as $review): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['reviewer_first_name'].' '.$review['reviewer_last_name']); ?></h6>
                                                <div class="d-flex align-items-center">
                                                    <?php for ($i=1; $i<=5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <p class="text-muted mb-1">Skill: <?php echo htmlspecialchars($review['skill_name']); ?></p>
                                            <?php if (!empty($review['comment'])): ?>
                                            <p class="mb-0">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                                            <?php endif; ?>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted">No reviews yet. Complete skill exchanges to receive reviews from other users.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Matches -->
                    <div class="card mt-4">
                        <div class="card-header"><h5 class="mb-0">My Matches</h5></div>
                        <div class="card-body">
                            <?php if (count($userMatches) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($userMatches as $match):
                                        $otherUser = ($match['user1_id'] == $user_id) ? 
                                            ['id'=>$match['user2_id'],'name'=>$match['user2_first_name'].' '.$match['user2_last_name'],'profile_image'=>$match['user2_profile_image']] :
                                            ['id'=>$match['user1_id'],'name'=>$match['user1_first_name'].' '.$match['user1_last_name'],'profile_image'=>$match['user1_profile_image']];
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/avatars/<?php echo $otherUser['profile_image'] ?: 'default.png'; ?>" class="rounded-circle me-3" width="48" height="48">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($otherUser['name']); ?></h6>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($match['teach_skill']); ?> for <?php echo htmlspecialchars($match['learn_skill']); ?></p>
                                                <small class="text-muted">Matched on <?php echo date('M j, Y', strtotime($match['matched_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-<?php echo ($match['status']=='accepted'?'success':($match['status']=='pending'?'warning':($match['status']=='completed'?'info':'secondary'))); ?> me-2"><?php echo ucfirst($match['status']); ?></span>

                                            <!-- Accept Button -->
                                            <?php if ($match['status']=='pending' && $match['user2_id']==$user_id): ?>
                                            <form method="POST" class="d-inline me-2">
                                                <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                                <button type="submit" name="accept_match" class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i> Accept</button>
                                            </form>
                                            <?php endif; ?>

                                            <!-- Mark as Completed Button -->
                                            <?php if ($match['status']=='accepted'): ?>
                                            <form method="POST" class="d-inline me-2">
                                                <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                                <button type="submit" name="complete_match" class="btn btn-sm btn-info"><i class="fas fa-check-double me-1"></i> Mark Completed</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">You don't have any matches yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('match_id')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('reviewed_id').value = selectedOption.dataset.userId;
    });
</script>
</body>
</html>
