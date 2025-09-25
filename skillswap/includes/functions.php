<?php
require_once 'database.php';

class Functions {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    // Sanitize input data
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    // Get all skills
    public function getAllSkills() {
        $stmt = $this->conn->prepare("SELECT * FROM skills ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Get user skills
    public function getUserSkills($user_id, $type = null) {
        $sql = "SELECT us.*, s.name, s.category 
                FROM user_skills us 
                JOIN skills s ON us.skill_id = s.id 
                WHERE us.user_id = ?";
        
        if ($type) {
            $sql .= " AND us.type = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$user_id, $type]);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$user_id]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Remove user skill
    public function removeUserSkill($user_skill_id) {
        $stmt = $this->conn->prepare("DELETE FROM user_skills WHERE id = ?");
        return $stmt->execute([$user_skill_id]);
    }

    // Find matches for a user
    public function findMatches($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.*, s_teach.name as teach_skill, s_learn.name as learn_skill,
                   us_teach.id as teach_skill_id, us_learn.id as learn_skill_id
            FROM users u
            JOIN user_skills us_teach ON u.id = us_teach.user_id AND us_teach.type = 'teach'
            JOIN user_skills us_learn ON u.id = us_learn.user_id AND us_learn.type = 'learn'
            JOIN skills s_teach ON us_teach.skill_id = s_teach.id
            JOIN skills s_learn ON us_learn.skill_id = s_learn.id
            WHERE u.id != ? AND u.is_active = TRUE
            AND us_teach.skill_id IN (
                SELECT skill_id FROM user_skills 
                WHERE user_id = ? AND type = 'learn'
            )
            AND us_learn.skill_id IN (
                SELECT skill_id FROM user_skills 
                WHERE user_id = ? AND type = 'teach'
            )
            GROUP BY u.id
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Create a match
    public function createMatch($user1_id, $user2_id, $skill_teach_id, $skill_learn_id) {
        $stmt = $this->conn->prepare("
            INSERT INTO matches (user1_id, user2_id, skill_teach_id, skill_learn_id) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$user1_id, $user2_id, $skill_teach_id, $skill_learn_id]);
    }

    // Get user matches
    public function getUserMatches($user_id) {
        $stmt = $this->conn->prepare("
            SELECT m.*, 
                   u1.username as user1_username, u1.first_name as user1_first_name, 
                   u1.last_name as user1_last_name, u1.profile_image as user1_profile_image,
                   u2.username as user2_username, u2.first_name as user2_first_name, 
                   u2.last_name as user2_last_name, u2.profile_image as user2_profile_image,
                   s_teach.name as teach_skill, s_learn.name as learn_skill
            FROM matches m
            JOIN users u1 ON m.user1_id = u1.id
            JOIN users u2 ON m.user2_id = u2.id
            JOIN skills s_teach ON m.skill_teach_id = s_teach.id
            JOIN skills s_learn ON m.skill_learn_id = s_learn.id
            WHERE (m.user1_id = ? OR m.user2_id = ?)
            ORDER BY m.matched_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Get match messages with read status
    public function getMatchMessages($match_id) {
        $stmt = $this->conn->prepare("
            SELECT m.*, u.username, u.first_name, u.last_name, u.profile_image
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.match_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$match_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Send message
    public function sendMessage($match_id, $sender_id, $content, $message_type = 'text') {
        $stmt = $this->conn->prepare("
            INSERT INTO messages (match_id, sender_id, message_type, content) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$match_id, $sender_id, $message_type, $content]);
    }

    // Mark messages as read for a specific conversation
    public function markMessagesAsRead($match_id, $user_id) {
        $stmt = $this->conn->prepare("
            UPDATE messages 
            SET is_read = TRUE 
            WHERE match_id = ? 
            AND sender_id != ? 
            AND is_read = FALSE
        ");
        return $stmt->execute([$match_id, $user_id]);
    }

    // Get unread message count for a specific conversation
    public function getUnreadMessageCount($match_id, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM messages 
            WHERE match_id = ? 
            AND sender_id != ? 
            AND is_read = FALSE
        ");
        $stmt->execute([$match_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['unread_count'] ?? 0;
    }

    // Get total unread messages count for user
    public function getTotalUnreadMessages($user_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total_unread 
            FROM messages m
            JOIN matches mt ON m.match_id = mt.id
            WHERE m.sender_id != ? 
            AND m.is_read = FALSE
            AND (mt.user1_id = ? OR mt.user2_id = ?)
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_unread'] ?? 0;
    }

    // Get user reviews
   /* public function getUserReviews($user_id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, 
                   reviewer.username as reviewer_username, 
                   reviewer.first_name as reviewer_first_name,
                   reviewer.last_name as reviewer_last_name,
                   s.name as skill_name
            FROM reviews r
            JOIN users reviewer ON r.reviewer_id = reviewer.id
            JOIN matches m ON r.match_id = m.id
            JOIN skills s ON m.skill_teach_id = s.id OR m.skill_learn_id = s.id
            WHERE r.reviewed_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Add review
    public function addReview($match_id, $reviewer_id, $reviewed_id, $rating, $comment) {
        $stmt = $this->conn->prepare("
            INSERT INTO reviews (match_id, reviewer_id, reviewed_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$match_id, $reviewer_id, $reviewed_id, $rating, $comment]);
    }*/

    // Get user stats
    public function getUserStats($user_id) {
        $stats = [];

        // Total matches
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total_matches 
            FROM matches 
            WHERE (user1_id = ? OR user2_id = ?) AND status != 'rejected'
        ");
        $stmt->execute([$user_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_matches'] = isset($result['total_matches']) ? (int)$result['total_matches'] : 0;

        // Unread messages
        $stats['unread_messages'] = $this->getTotalUnreadMessages($user_id);

        // Average rating
        $stmt = $this->conn->prepare("
            SELECT AVG(rating) as avg_rating 
            FROM reviews 
            WHERE reviewed_id = ?
        ");
        $stmt->execute([$user_id]);
        $avg = $stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'];
        $stats['avg_rating'] = $avg !== null ? round((float)$avg, 1) : 0;

        // Skills offered
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as skills_offered 
            FROM user_skills 
            WHERE user_id = ? AND type = 'teach'
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['skills_offered'] = isset($result['skills_offered']) ? (int)$result['skills_offered'] : 0;

        return $stats;
    }

    // =======================
    // NOTIFICATIONS MANAGEMENT
    // =======================

    // Get unread notifications count (includes message notifications)
    public function getUnreadNotificationsCount($user_id) {
        // Get regular notifications count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        $notificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Get unread messages count
        $unreadMessages = $this->getTotalUnreadMessages($user_id);
        
        return $notificationCount + $unreadMessages;
    }

    // Get notifications
    public function getNotifications($user_id, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Mark notification as read
    public function markNotificationAsRead($notification_id, $user_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    }

    // Mark all notifications as read (with optional type filter)
    public function markAllNotificationsAsRead($user_id, $type = null) {
        if ($type) {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND type = ? AND is_read = FALSE
            ");
            return $stmt->execute([$user_id, $type]);
        } else {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND is_read = FALSE
            ");
            return $stmt->execute([$user_id]);
        }
    }

    // Mark match notifications as read
    public function markMatchNotificationsAsRead($user_id, $match_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? 
            AND type = 'message' 
            AND related_id = ? 
            AND is_read = FALSE
        ");
        return $stmt->execute([$user_id, $match_id]);
    }

    // Create notification
    public function createNotification($user_id, $type, $title, $message, $related_id = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $type, $title, $message, $related_id]);
    }

    // =======================
    // MATCH MANAGEMENT
    // =======================

    // Update match status
    public function updateMatchStatus($match_id, $status, $user_id) {
        $stmt = $this->conn->prepare("
            UPDATE matches 
            SET status = ? 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?)
        ");
        return $stmt->execute([$status, $match_id, $user_id, $user_id]);
    }

    // Get match details
    public function getMatchDetails($match_id) {
        $stmt = $this->conn->prepare("
            SELECT m.*, 
                   u1.username as user1_username, u1.first_name as user1_first_name,
                   u2.username as user2_username, u2.first_name as user2_first_name,
                   s_teach.name as teach_skill, s_learn.name as learn_skill
            FROM matches m
            JOIN users u1 ON m.user1_id = u1.id
            JOIN users u2 ON m.user2_id = u2.id
            JOIN skills s_teach ON m.skill_teach_id = s_teach.id
            JOIN skills s_learn ON m.skill_learn_id = s_learn.id
            WHERE m.id = ?
        ");
        $stmt->execute([$match_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Accept match
    public function acceptMatch($match_id, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM matches 
            WHERE id = ? AND user2_id = ? AND status = 'pending'
        ");
        $stmt->execute([$match_id, $user_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) return false;

        $stmt = $this->conn->prepare("UPDATE matches SET status = 'accepted' WHERE id = ?");
        return $stmt->execute([$match_id]);
    }

    // =======================
    // SKILLS MANAGEMENT
    // =======================

    // Add custom skill if not predefined
    public function addCustomSkill($skill_name, $category = 'General') {
        $skill_name = trim($skill_name);

        // Check if skill already exists
        $stmt = $this->conn->prepare("SELECT id FROM skills WHERE name = ?");
        $stmt->execute([$skill_name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return $existing['id'];
        }

        // Insert new skill
        $stmt = $this->conn->prepare("INSERT INTO skills (name, category) VALUES (?, ?)");
        if ($stmt->execute([$skill_name, $category])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Add user skill (handles predefined + custom)
    public function addUserSkill($user_id, $skill_id_or_name, $type, $proficiency = 'intermediate', $description = '') {
        // If user entered text instead of skill id, treat it as custom
        if (!is_numeric($skill_id_or_name)) {
            $skill_id = $this->addCustomSkill($skill_id_or_name);
            if (!$skill_id) return false;
        } else {
            $skill_id = $skill_id_or_name;
        }

        // Check if user already has this skill
        $stmt = $this->conn->prepare("
            SELECT id FROM user_skills 
            WHERE user_id = ? AND skill_id = ? AND type = ?
        ");
        $stmt->execute([$user_id, $skill_id, $type]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return true; // Skill already exists
        }

        $stmt = $this->conn->prepare("
            INSERT INTO user_skills (user_id, skill_id, type, proficiency, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $skill_id, $type, $proficiency, $description]);
    }

    // =======================
    // USER MANAGEMENT
    // =======================

    // Get user by ID
    public function getUserById($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Update user profile
    public function updateUserProfile($user_id, $data) {
        $allowed_fields = ['first_name', 'last_name', 'email', 'bio', 'location', 'profile_image'];
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) return false;

        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Change user password
    public function changePassword($user_id, $current_password, $new_password) {
        // Verify current password
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            return false;
        }

        // Update password
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$new_hashed_password, $user_id]);
    }

    // =======================
    // UTILITY METHODS
    // =======================

    // Validate email
    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Validate password strength
    public function isStrongPassword($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        return true;
    }

    // Log user activity
    public function logActivity($user_id, $activity_type, $description, $related_id = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_activities (user_id, activity_type, description, related_id) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $activity_type, $description, $related_id]);
    }

    // Get recent activities
    public function getRecentActivities($user_id, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM user_activities 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Check if users are matched
    public function areUsersMatched($user1_id, $user2_id) {
        $stmt = $this->conn->prepare("
            SELECT id FROM matches 
            WHERE ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)) 
            AND status = 'accepted'
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // Close database connection
    public function closeConnection() {
        $this->conn = null;
    }

    // Destructor
    public function __destruct() {
        $this->closeConnection();
    }    
    // =======================
    // REVIEWS & RATINGS SYSTEM
    // =======================
    
    // Check if user can review a match
    public function canReviewMatch($match_id, $reviewer_id) {
        $stmt = $this->conn->prepare("
            SELECT m.* 
            FROM matches m
            WHERE m.id = ? 
            AND (m.user1_id = ? OR m.user2_id = ?)
            AND m.status IN ('accepted', 'completed')
            AND NOT EXISTS (
                SELECT 1 FROM reviews r 
                WHERE r.match_id = m.id AND r.reviewer_id = ?
            )
        ");
        $stmt->execute([$match_id, $reviewer_id, $reviewer_id, $reviewer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    // Add review with validation
    public function addReview($match_id, $reviewer_id, $reviewed_id, $rating, $comment = '') {
        // Validate rating
        $rating = (int)$rating;
        if ($rating < 1 || $rating > 5) {
            return false;
        }
        
        // Check if user can review this match
        if (!$this->canReviewMatch($match_id, $reviewer_id)) {
            return false;
        }
        
        // Check if users are actually in this match
        $stmt = $this->conn->prepare("
            SELECT * FROM matches 
            WHERE id = ? AND ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
        ");
        $stmt->execute([$match_id, $reviewer_id, $reviewed_id, $reviewed_id, $reviewer_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$match) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO reviews (match_id, reviewer_id, reviewed_id, rating, comment) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$match_id, $reviewer_id, $reviewed_id, $rating, $comment])) {
            // Create notification for the reviewed user
            $reviewer = $this->getUserById($reviewer_id);
            $this->createNotification(
                $reviewed_id,
                'review',
                'New Review Received',
                $reviewer['first_name'] . ' gave you a ★' . $rating . ' review',
                $match_id
            );
            
            // Update user's average rating
            $this->updateUserRating($reviewed_id);
            
            return true;
        }
        
        return false;
    }
    
    // Get user's average rating and review count
    public function getUserRatingStats($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                AVG(rating) as average_rating,
                COUNT(*) as review_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM reviews 
            WHERE reviewed_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $stats['average_rating'] = round((float)$stats['average_rating'], 1);
            $stats['average_rating_formatted'] = number_format($stats['average_rating'], 1);
        } else {
            $stats = [
                'average_rating' => 0,
                'average_rating_formatted' => '0.0',
                'review_count' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0
            ];
        }
        
        return $stats;
    }
    
    // Update user's average rating in users table
    public function updateUserRating($user_id) {
        $stats = $this->getUserRatingStats($user_id);
        
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET average_rating = ?, review_count = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$stats['average_rating'], $stats['review_count'], $user_id]);
    }
    
    // Get reviews for a user
    public function getUserReviews($user_id, $limit = null) {
    $sql = "
        SELECT r.*, 
               reviewer.username as reviewer_username, 
               reviewer.first_name as reviewer_first_name,
               reviewer.last_name as reviewer_last_name,
               reviewer.profile_image as reviewer_profile_image,
               s_teach.name as teach_skill,
               s_learn.name as learn_skill,
               m.matched_at,
               s_teach.name AS skill_name  -- Add this for backward compatibility
        FROM reviews r
        JOIN users reviewer ON r.reviewer_id = reviewer.id
        JOIN matches m ON r.match_id = m.id
        JOIN skills s_teach ON m.skill_teach_id = s_teach.id
        JOIN skills s_learn ON m.skill_learn_id = s_learn.id
        WHERE r.reviewed_id = ?
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $this->conn->prepare($sql);

    if ($limit) {
        // PDO does not support LIMIT with placeholders in all versions; bindValue ensures it works as integer
        $stmt = $this->conn->prepare($sql . " LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt->execute([$user_id]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

    
    // Get reviews written by a user
    public function getReviewsWritten($user_id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, 
                   reviewed.username as reviewed_username, 
                   reviewed.first_name as reviewed_first_name,
                   reviewed.last_name as reviewed_last_name,
                   reviewed.profile_image as reviewed_profile_image,
                   s_teach.name as teach_skill,
                   s_learn.name as learn_skill
            FROM reviews r
            JOIN users reviewed ON r.reviewed_id = reviewed.id
            JOIN matches m ON r.match_id = m.id
            JOIN skills s_teach ON m.skill_teach_id = s_teach.id
            JOIN skills s_learn ON m.skill_learn_id = s_learn.id
            WHERE r.reviewer_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    // Mark match as completed
    public function completeMatch($match_id, $user_id) {
        // Verify user is part of the match and it's accepted
        $stmt = $this->conn->prepare("
            SELECT * FROM matches 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?) AND status = 'accepted'
        ");
        $stmt->execute([$match_id, $user_id, $user_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$match) {
            return false;
        }
        
        $stmt = $this->conn->prepare("UPDATE matches SET status = 'completed' WHERE id = ?");
        return $stmt->execute([$match_id]);
    }
    
    // Get matches available for review
    public function getReviewableMatches($user_id) {
        $stmt = $this->conn->prepare("
            SELECT m.*,
                   u1.first_name as user1_first_name, u1.last_name as user1_last_name, u1.profile_image as user1_profile_image,
                   u2.first_name as user2_first_name, u2.last_name as user2_last_name, u2.profile_image as user2_profile_image,
                   s_teach.name as teach_skill, s_learn.name as learn_skill,
                   CASE 
                     WHEN m.user1_id = ? THEN m.user2_id
                     ELSE m.user1_id
                   END as other_user_id,
                   CASE 
                     WHEN m.user1_id = ? THEN CONCAT(u2.first_name, ' ', u2.last_name)
                     ELSE CONCAT(u1.first_name, ' ', u1.last_name)
                   END as other_user_name
            FROM matches m
            JOIN users u1 ON m.user1_id = u1.id
            JOIN users u2 ON m.user2_id = u2.id
            JOIN skills s_teach ON m.skill_teach_id = s_teach.id
            JOIN skills s_learn ON m.skill_learn_id = s_learn.id
            WHERE (m.user1_id = ? OR m.user2_id = ?)
            AND m.status IN ('accepted', 'completed')
            AND NOT EXISTS (
                SELECT 1 FROM reviews r 
                WHERE r.match_id = m.id AND r.reviewer_id = ?
            )
            ORDER BY m.matched_at DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    // Get top rated users
    public function getTopRatedUsers($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT u.*, 
                   AVG(r.rating) as avg_rating,
                   COUNT(r.id) as review_count
            FROM users u
            LEFT JOIN reviews r ON u.id = r.reviewed_id
            WHERE u.is_active = TRUE
            GROUP BY u.id
            HAVING COUNT(r.id) >= 1
            ORDER BY avg_rating DESC, review_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    // Get recent reviews across the platform
    public function getRecentReviews($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT r.*,
                   reviewer.first_name as reviewer_first_name,
                   reviewer.last_name as reviewer_last_name,
                   reviewer.profile_image as reviewer_profile_image,
                   reviewed.first_name as reviewed_first_name,
                   reviewed.last_name as reviewed_last_name,
                   s_teach.name as teach_skill,
                   s_learn.name as learn_skill
            FROM reviews r
            JOIN users reviewer ON r.reviewer_id = reviewer.id
            JOIN users reviewed ON r.reviewed_id = reviewed.id
            JOIN matches m ON r.match_id = m.id
            JOIN skills s_teach ON m.skill_teach_id = s_teach.id
            JOIN skills s_learn ON m.skill_learn_id = s_learn.id
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    // Check if match can be completed
    public function canCompleteMatch($match_id, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM matches 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?) AND status = 'accepted'
        ");
        $stmt->execute([$match_id, $user_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

public function getTotalUnreadCount($user_id) {
        // Get regular notifications count
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        $notificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Get unread messages count
        $unreadMessages = $this->getTotalUnreadMessages($user_id);
        
        return $notificationCount + $unreadMessages;
    }


}
?>