<?php
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$currentUser = $auth->getCurrentUser(); // Fetch current user at the start

// Define available avatars
$availableAvatars = [
    'avatar1.png' => 'Avatar 1',
    'avatar2.png' => 'Avatar 2', 
    'avatar3.png' => 'Avatar 3',
    'avatar4.png' => 'Avatar 4'
];

// -------------------------
// HANDLE AVATAR SELECTION
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_avatar'])) {
    $selected_avatar = $_POST['avatar'] ?? '';
    
    if (empty($selected_avatar)) {
        $error = 'Please select an avatar.';
    } elseif (!array_key_exists($selected_avatar, $availableAvatars)) {
        $error = 'Invalid avatar selection.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$selected_avatar, $_SESSION['user_id']])) {
                $message = 'Avatar updated successfully!';
                $_SESSION['profile_image'] = $selected_avatar;
                $currentUser['profile_image'] = $selected_avatar; // Update current user immediately
                
                // Clear any uploaded profile image if exists
                if ($currentUser['profile_image'] && !in_array($currentUser['profile_image'], array_keys($availableAvatars))) {
                    $old_image_path = '../' . UPLOAD_DIR . '/' . $currentUser['profile_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                $error = 'Failed to update avatar: ' . $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// -------------------------
// HANDLE PASSWORD CHANGE
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    error_log("DEBUG: Password change form submitted for user ID " . $_SESSION['user_id']);

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Adjust this if your DB column is "password" instead of "password_hash"
    $passwordColumn = "password_hash";

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Fetch current password hash
            $stmt = $conn->prepare("SELECT $passwordColumn FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current_password, $user[$passwordColumn])) {
                // Hash and update new password
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET $passwordColumn = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$new_hashed_password, $_SESSION['user_id']])) {
                    $message = 'Password updated successfully!';
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $error = 'Failed to update password: ' . $errorInfo[2];
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }

    // Safety net: if nothing was set, show a fallback message
    if (!$message && !$error) {
        $error = "Password change attempted, but no feedback was generated.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .avatar-selection {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .avatar-option {
            text-align: center;
            cursor: pointer;
            padding: 1rem;
            border: 2px solid #dee2e6;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .avatar-option:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        
        .avatar-option.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
        }
        
        .avatar-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 0.5rem;
        }
        
        .avatar-name {
            font-weight: 500;
            color: #495057;
        }
        
        .current-avatar {
            border: 3px solid #0d6efd;
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
        }
        
        .password-strength {
            height: 4px;
            margin-top: 0.25rem;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #fd7e14; width: 50%; }
        .strength-good { background-color: #ffc107; width: 75%; }
        .strength-strong { background-color: #198754; width: 100%; }
    </style>
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
                    <?php if ($isLoggedIn): ?>
                    <div class="dropdown me-3">
                        <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadNotifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadNotifications; ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="notifications.php">View All Notifications</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- User Profile -->
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="../assets/images/avatars/<?php echo $currentUser['profile_image'] ?? 'default.png'; ?>" 
                                 alt="Profile" class="rounded-circle me-2" width="32" height="32"
                                 onerror="this.src='../assets/images/avatars/default.png'">
                            <?php echo htmlspecialchars($currentUser['first_name'] ?? 'User'); ?>
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
                                <a class="nav-link" href="matches.php">
                                    <i class="fas fa-handshake me-2"></i>
                                    Find Matches
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="messages.php">
                                    <i class="fas fa-comments me-2"></i>
                                    Messages
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reviews.php">
                                    <i class="fas fa-star me-2"></i>
                                    Reviews
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="settings.php">
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
                            <h2>Account Settings</h2>
                        </div>

                        <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Avatar Selection -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Profile Avatar</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Current Avatar Display -->
                                        <div class="text-center mb-4">
                                            <img src="../assets/images/avatars/<?php echo $currentUser['profile_image'] ?? 'default.png'; ?>" 
                                                 alt="Current Avatar" class="avatar-image current-avatar" 
                                                 onerror="this.src='../assets/images/avatars/default.png'">
                                            <div class="mt-2">
                                                <small class="text-muted">Current Avatar</small>
                                            </div>
                                        </div>

                                        <!-- Avatar Selection Form -->
                                        <form method="POST" action="" id="avatarForm">
                                            <div class="avatar-selection">
                                                <?php foreach ($availableAvatars as $avatarFile => $avatarName): ?>
                                                <label class="avatar-option <?php echo ($currentUser['profile_image'] === $avatarFile) ? 'selected' : ''; ?>">
                                                    <input type="radio" name="avatar" value="<?php echo $avatarFile; ?>" 
                                                           <?php echo ($currentUser['profile_image'] === $avatarFile) ? 'checked' : ''; ?>
                                                           style="display: none;">
                                                    <img src="../assets/images/avatars/<?php echo $avatarFile; ?>" 
                                                         alt="<?php echo $avatarName; ?>" class="avatar-image"
                                                         onerror="this.src='../assets/images/avatars/default.png'">
                                                    <div class="avatar-name"><?php echo $avatarName; ?></div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="submit" name="change_avatar" class="btn btn-primary w-100">
                                                Change Avatar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Change Password</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="" id="passwordForm">
                                            <div class="mb-3">
                                                <label for="current_password" class="form-label">Current Password</label>
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required 
                                                       minlength="8" oninput="checkPasswordStrength(this.value)">
                                                <div class="password-strength" id="passwordStrength"></div>
                                                <div class="form-text" id="passwordHelp">
                                                    Password must be at least 8 characters long
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <div class="form-text" id="confirmHelp"></div>
                                            </div>
                                            <button type="submit" name="change_password" class="btn btn-primary" id="passwordSubmit">
                                                Change Password
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Account Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username'] ?? 'N/A'); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email'] ?? 'N/A'); ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['first_name'] ?? 'N/A'); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['last_name'] ?? 'N/A'); ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Member Since</label>
                                            <input type="text" class="form-control" value="<?php 
                                                echo !empty($currentUser['created_at']) ? date('M j, Y', strtotime($currentUser['created_at'])) : 'N/A';
                                            ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Last Updated</label>
                                            <input type="text" class="form-control" value="<?php 
                                                if (!empty($currentUser['updated_at']) && $currentUser['updated_at'] !== $currentUser['created_at']) {
                                                    echo date('M j, Y', strtotime($currentUser['updated_at']));
                                                } else {
                                                    echo 'Never updated';
                                                }
                                            ?>" disabled>
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
    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Avatar selection functionality
        document.querySelectorAll('.avatar-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.avatar-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio input
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Update current avatar preview
                const avatarImg = this.querySelector('img').src;
                document.querySelector('.current-avatar').src = avatarImg;
            });
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            const helpText = document.getElementById('passwordHelp');
            let strength = 0;
            let feedback = [];

            // Length check
            if (password.length >= 8) strength++;
            else feedback.push('at least 8 characters');

            // Complexity checks
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letters');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letters');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('numbers');
            
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            else feedback.push('special characters');

            // Update strength bar
            strengthBar.className = 'password-strength';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                helpText.textContent = 'Password must be at least 8 characters long';
            } else {
                switch(strength) {
                    case 0:
                    case 1:
                        strengthBar.classList.add('strength-weak');
                        helpText.textContent = 'Very weak password';
                        break;
                    case 2:
                        strengthBar.classList.add('strength-fair');
                        helpText.textContent = 'Fair password';
                        break;
                    case 3:
                        strengthBar.classList.add('strength-good');
                        helpText.textContent = 'Good password';
                        break;
                    case 4:
                    case 5:
                        strengthBar.classList.add('strength-strong');
                        helpText.textContent = 'Strong password!';
                        break;
                }
            }
        }

        // Password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmHelp = document.getElementById('confirmHelp');
            
            if (this.value !== newPassword) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                confirmHelp.textContent = 'Passwords do not match';
                confirmHelp.className = 'form-text text-danger';
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                confirmHelp.textContent = 'Passwords match!';
                confirmHelp.className = 'form-text text-success';
            }
        });

        // Handle form submissions
        document.getElementById('avatarForm').addEventListener('submit', function(e) {
            const selectedAvatar = document.querySelector('input[name="avatar"]:checked');
            if (!selectedAvatar) {
                e.preventDefault();
                alert('Please select an avatar');
                return;
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitButton = document.getElementById('passwordSubmit');
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Updating...';
        });
    </script>
</body>
</html>