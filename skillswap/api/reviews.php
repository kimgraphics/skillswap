<?php
require_once 'config.php';

// Set content type
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
            
        case 'POST':
            handlePostRequest();
            break;
            
        case 'PUT':
            handlePutRequest();
            break;
            
        case 'DELETE':
            handleDeleteRequest();
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Reviews API Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}

function handleGetRequest() {
    global $auth, $functions;
    
    requireAuth();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'user_reviews':
                getUserReviews();
                break;
                
            case 'match_reviews':
                getMatchReviews();
                break;
                
            case 'stats':
                getReviewStats();
                break;
                
            default:
                sendError('Invalid action');
        }
    } else {
        sendError('Action parameter required');
    }
}

function handlePostRequest() {
    global $auth;
    
    requireAuth();
    
    $input = getJsonInput();
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'submit_review':
                submitReview($input);
                break;
                
            case 'update_review':
                updateReview($input);
                break;
                
            default:
                sendError('Invalid action');
        }
    } else {
        sendError('Action parameter required');
    }
}

function handlePutRequest() {
    sendError('PUT method not implemented', 501);
}

function handleDeleteRequest() {
    sendError('DELETE method not implemented', 501);
}

function getUserReviews() {
    global $functions;
    
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
    $type = $_GET['type'] ?? 'received'; // 'received' or 'given'
    
    if ($type === 'received') {
        $reviews = $functions->getUserReviews($user_id);
    } else {
        $reviews = $functions->getReviewsGivenByUser($user_id);
    }
    
    // Format response
    $formatted_reviews = [];
    foreach ($reviews as $review) {
        $formatted_reviews[] = [
            'id' => $review['id'],
            'rating' => (int)$review['rating'],
            'comment' => $review['comment'],
            'created_at' => $review['created_at'],
            'reviewer' => [
                'id' => $review['reviewer_id'],
                'name' => $review['reviewer_first_name'] . ' ' . $review['reviewer_last_name'],
                'username' => $review['reviewer_username']
            ],
            'reviewed_user' => [
                'id' => $review['reviewed_id'],
                'name' => $review['reviewed_first_name'] . ' ' . $review['reviewed_last_name'],
                'username' => $review['reviewed_username']
            ],
            'match' => [
                'id' => $review['match_id'],
                'teach_skill' => $review['skill_name'],
                'learn_skill' => $review['learn_skill_name'] ?? ''
            ]
        ];
    }
    
    sendResponse($formatted_reviews, 'Reviews retrieved successfully');
}

function getMatchReviews() {
    global $functions;
    
    $match_id = $_GET['match_id'] ?? null;
    
    if (!$match_id) {
        sendError('Match ID required');
    }
    
    // Verify user has access to this match
    $user_id = $_SESSION['user_id'];
    $match = $functions->getMatchDetails($match_id);
    
    if (!$match || ($match['user1_id'] != $user_id && $match['user2_id'] != $user_id)) {
        sendError('Access denied to this match', 403);
    }
    
    $reviews = $functions->getReviewsForMatch($match_id);
    
    sendResponse($reviews, 'Match reviews retrieved successfully');
}

function getReviewStats() {
    global $functions;
    
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
    
    $stats = $functions->getUserReviewStats($user_id);
    
    sendResponse($stats, 'Review statistics retrieved successfully');
}

function submitReview($input) {
    global $functions;
    
    validateRequired($input, ['match_id', 'reviewed_user_id', 'rating']);
    
    $match_id = $input['match_id'];
    $reviewed_user_id = $input['reviewed_user_id'];
    $rating = (int)$input['rating'];
    $comment = trim($input['comment'] ?? '');
    $reviewer_id = $_SESSION['user_id'];
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        sendError('Rating must be between 1 and 5');
    }
    
    // Validate comment length
    if (strlen($comment) > 500) {
        sendError('Comment too long (max 500 characters)');
    }
    
    // Verify user has access to this match
    $match = $functions->getMatchDetails($match_id);
    
    if (!$match || ($match['user1_id'] != $reviewer_id && $match['user2_id'] != $reviewer_id)) {
        sendError('Access denied to this match', 403);
    }
    
    // Verify reviewed user is in the match
    if ($match['user1_id'] != $reviewed_user_id && $match['user2_id'] != $reviewed_user_id) {
        sendError('Reviewed user is not part of this match', 400);
    }
    
    // Check if review already exists
    $existing_review = $functions->getReviewByMatchAndReviewer($match_id, $reviewer_id);
    
    if ($existing_review) {
        sendError('You have already submitted a review for this match');
    }
    
    // Check if match is completed
    if ($match['status'] !== 'completed') {
        sendError('Can only review completed matches');
    }
    
    // Submit review
    $review_id = $functions->addReview($match_id, $reviewer_id, $reviewed_user_id, $rating, $comment);
    
    if ($review_id) {
        // Create notification for the reviewed user
        $functions->createNotification(
            $reviewed_user_id,
            'review',
            'New Review Received',
            $_SESSION['first_name'] . ' left you a review',
            $match_id
        );
        
        // Get the submitted review details
        $review = $functions->getReviewById($review_id);
        
        sendResponse($review, 'Review submitted successfully');
    } else {
        sendError('Failed to submit review');
    }
}

function updateReview($input) {
    global $functions;
    
    validateRequired($input, ['review_id', 'rating']);
    
    $review_id = $input['review_id'];
    $rating = (int)$input['rating'];
    $comment = trim($input['comment'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        sendError('Rating must be between 1 and 5');
    }
    
    // Validate comment length
    if (strlen($comment) > 500) {
        sendError('Comment too long (max 500 characters)');
    }
    
    // Verify user owns this review
    $review = $functions->getReviewById($review_id);
    
    if (!$review || $review['reviewer_id'] != $user_id) {
        sendError('Access denied to this review', 403);
    }
    
    // Check if review can be updated (within 24 hours)
    $review_time = strtotime($review['created_at']);
    $current_time = time();
    $time_diff = $current_time - $review_time;
    
    if ($time_diff > 24 * 60 * 60) { // 24 hours
        sendError('Reviews can only be updated within 24 hours of submission');
    }
    
    // Update review
    $success = $functions->updateReview($review_id, $rating, $comment);
    
    if ($success) {
        $updated_review = $functions->getReviewById($review_id);
        sendResponse($updated_review, 'Review updated successfully');
    } else {
        sendError('Failed to update review');
    }
}
?>