<?php
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$functions = new Functions();
$userMatches = $functions->getUserMatches($_SESSION['user_id']);

$selectedMatchId = $_GET['match_id'] ?? ($userMatches[0]['id'] ?? null);

// MARK MESSAGES AS READ WHEN VIEWING A CONVERSATION
if ($selectedMatchId) {
    // Mark all messages in this conversation as read for the current user
    $messagesMarked = $functions->markMessagesAsRead($selectedMatchId, $_SESSION['user_id']);
    
    // Also mark any related notifications as read
    $notificationsMarked = $functions->markMatchNotificationsAsRead($_SESSION['user_id'], $selectedMatchId);
    
    // Debug logging (remove in production)
    // error_log("Messages marked: " . ($messagesMarked ? 'true' : 'false'));
    // error_log("Notifications marked: " . ($notificationsMarked ? 'true' : 'false'));
}

$messages = $selectedMatchId ? $functions->getMatchMessages($selectedMatchId) : [];

$message = '';
$error = '';

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $match_id = $_POST['match_id'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if ($match_id && $content) {
        if ($functions->sendMessage($match_id, $_SESSION['user_id'], $content)) {
            // Create notification for the other user
            $match = $functions->getMatchDetails($match_id);
            $other_user_id = ($match['user1_id'] == $_SESSION['user_id']) ? $match['user2_id'] : $match['user1_id'];
            
            $functions->createNotification(
                $other_user_id,
                'message',
                'New Message',
                $_SESSION['first_name'] . ' sent you a message',
                $match_id
            );
            
            // Redirect to prevent form resubmission
            header('Location: messages.php?match_id=' . $match_id);
            exit;
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    } else {
        $error = 'Please enter a message.';
    }
}

// Get updated counts after marking messages as read
$unreadNotifications = $functions->getUnreadNotificationsCount($_SESSION['user_id']);
$totalUnread = $functions->getTotalUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .messages-container {
            height: 70vh;
            overflow-y: auto;
        }
        .message {
            max-width: 75%;
            margin-bottom: 1rem;
        }
        .message.sent {
            margin-left: auto;
        }
        .message.received {
            margin-right: auto;
        }
        .unread-badge {
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .conversation-unread {
            background-color: #f8f9fa;
            border-left: 3px solid #0d6efd;
        }
        .message.unread {
            background-color: #f0f8ff;
            border-left: 3px solid #0d6efd;
        }
    </style>
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
                                <a class="nav-link active" href="messages.php">
                                    <i class="fa-solid fa-comments me-2"></i>
                                    Messages
                                    <?php if ($totalUnread > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $totalUnread; ?></span>
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

                <!-- Main Content -->
                <div class="col-lg-10 main-content">
                    <div class="container-fluid py-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Messages</h2>
                            <?php if ($totalUnread > 0): ?>
                            <span class="badge bg-primary">
                                <?php echo $totalUnread; ?> unread message<?php echo $totalUnread > 1 ? 's' : ''; ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Conversations List -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Conversations</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php if (count($userMatches) > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($userMatches as $match): 
                                                $isUser1 = ($match['user1_id'] == $_SESSION['user_id']);
                                                $otherUserId = $isUser1 ? $match['user2_id'] : $match['user1_id'];
                                                $otherUserName = $isUser1 
                                                    ? $match['user2_first_name'] . ' ' . $match['user2_last_name'] 
                                                    : $match['user1_first_name'] . ' ' . $match['user1_last_name'];
                                                $otherUserImage = $isUser1 
                                                    ? ($match['user2_profile_image'] ?? 'default.png')
                                                    : ($match['user1_profile_image'] ?? 'default.png');
                                                $isActive = $selectedMatchId == $match['id'];
                                                
                                                // Get unread message count for this conversation
                                                $unreadCount = $functions->getUnreadMessageCount($match['id'], $_SESSION['user_id']);
                                            ?>
                                            <a href="?match_id=<?php echo $match['id']; ?>" 
                                               class="list-group-item list-group-item-action <?php echo $isActive ? 'active' : ''; ?> <?php echo $unreadCount > 0 ? 'conversation-unread' : ''; ?>">
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/avatars/<?php echo htmlspecialchars($otherUserImage); ?>" 
                                                         alt="Profile" class="rounded-circle me-3" width="40" height="40">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($otherUserName); ?></h6>
                                                        <small class="<?php echo $isActive ? 'text-light' : 'text-muted'; ?>">
                                                            <?php echo htmlspecialchars($match['teach_skill']); ?> for <?php echo htmlspecialchars($match['learn_skill']); ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($unreadCount > 0 && !$isActive): ?>
                                                    <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-<?php 
                                                        echo $match['status'] == 'accepted' ? 'success' : 
                                                             ($match['status'] == 'pending' ? 'warning' : 
                                                             ($match['status'] == 'completed' ? 'info' : 'secondary')); 
                                                    ?> ms-1">
                                                        <?php echo ucfirst($match['status']); ?>
                                                    </span>
                                                </div>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="p-3 text-muted">No conversations yet. <a href="matches.php">Find matches</a> to start messaging.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Area -->
                            <div class="col-md-8">
                                <?php if ($selectedMatchId): 
                                    $currentMatch = null;
                                    $otherUser = null;
                                    
                                    foreach ($userMatches as $match) {
                                        if ($match['id'] == $selectedMatchId) {
                                            $currentMatch = $match;
                                            $otherUser = ($match['user1_id'] == $_SESSION['user_id']) ? 
                                                [
                                                    'id' => $match['user2_id'], 
                                                    'name' => $match['user2_first_name'] . ' ' . $match['user2_last_name'],
                                                    'profile_image' => $match['user2_profile_image'] ?? 'default.png'
                                                ] :
                                                [
                                                    'id' => $match['user1_id'], 
                                                    'name' => $match['user1_first_name'] . ' ' . $match['user1_last_name'],
                                                    'profile_image' => $match['user1_profile_image'] ?? 'default.png'
                                                ];
                                            break;
                                        }
                                    }
                                ?>
                                <div class="card h-100">
                                    <div class="card-header d-flex align-items-center">
                                        <img src="../assets/images/avatars/<?php echo htmlspecialchars($otherUser['profile_image']); ?>" 
                                             alt="Profile" class="rounded-circle me-2" width="32" height="32">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($otherUser['name']); ?></h5>
                                        <span class="badge bg-<?php 
                                            echo $currentMatch['status'] == 'accepted' ? 'success' : 
                                                 ($currentMatch['status'] == 'pending' ? 'warning' : 
                                                 ($currentMatch['status'] == 'completed' ? 'info' : 'secondary')); 
                                        ?> ms-2">
                                            <?php echo ucfirst($currentMatch['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="card-body messages-container" id="messagesContainer">
                                        <?php if (count($messages) > 0): ?>
                                            <?php foreach ($messages as $message): 
                                                $isSent = $message['sender_id'] == $_SESSION['user_id'];
                                                $profileImage = $message['profile_image'] ?: 'default.png';
                                                $isUnread = !$message['is_read'] && !$isSent;
                                            ?>
                                            <div class="d-flex <?php echo $isSent ? 'justify-content-end' : 'justify-content-start'; ?> mb-2">
                                                <?php if (!$isSent): ?>
                                                    <img src="../assets/images/avatars/<?php echo htmlspecialchars($profileImage); ?>" 
                                                         class="rounded-circle me-2" width="32" height="32" alt="User">
                                                <?php endif; ?>

                                                <div class="message <?php echo $isSent ? 'sent' : 'received'; ?> <?php echo $isUnread ? 'unread' : ''; ?>">
                                                    <div class="card <?php echo $isSent ? 'bg-primary text-white' : 'bg-light'; ?>">
                                                        <div class="card-body p-2">
                                                            <p class="card-text mb-0"><?php echo htmlspecialchars($message['content']); ?></p>
                                                            <small class="<?php echo $isSent ? 'text-white-50' : 'text-muted'; ?>">
                                                                <?php echo date('M j, g:i a', strtotime($message['created_at'])); ?>
                                                                <?php if ($isSent): ?>
                                                                    <?php if ($message['is_read']): ?>
                                                                        <i class="fas fa-check-double text-info ms-1" title="Read"></i>
                                                                    <?php else: ?>
                                                                        <i class="fas fa-check text-light ms-1" title="Sent"></i>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($isSent): ?>
                                                    <img src="../assets/images/avatars/<?php echo htmlspecialchars($profileImage); ?>" 
                                                         class="rounded-circle ms-2" width="32" height="32" alt="You">
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="fa-solid fa-comments fa-3x mb-3"></i>
                                            <p>No messages yet. Start the conversation!</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <form method="POST" action="" id="messageForm">
                                            <input type="hidden" name="match_id" value="<?php echo $selectedMatchId; ?>">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="content" placeholder="Type your message..." required id="messageInput">
                                                <button type="submit" name="send_message" class="btn btn-primary">
                                                    <i class="fa-solid fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="card h-100">
                                    <div class="card-body d-flex align-items-center justify-content-center">
                                        <div class="text-center text-muted">
                                            <i class="fa-solid fa-comments fa-3x mb-3"></i>
                                            <h5>No conversation selected</h5>
                                            <p>Select a conversation from the list or <a href="matches.php">find matches</a> to start messaging.</p>
                                        </div>
                                    </div>
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
    <script>
        // Auto-scroll to bottom of messages
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Focus on message input
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.focus();
            }
        });

        // Auto-refresh messages every 10 seconds
        setInterval(function() {
            if (window.location.search.includes('match_id')) {
                window.location.reload();
            }
        }, 10000);
    </script>
</body>
</html>