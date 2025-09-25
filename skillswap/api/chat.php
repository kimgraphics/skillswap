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
    error_log("API Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}

function handleGetRequest() {
    global $auth, $functions;
    
    requireAuth();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'messages':
                getMessages();
                break;
                
            case 'conversations':
                getConversations();
                break;
                
            case 'unread_count':
                getUnreadCount();
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
            case 'send_message':
                sendMessage($input);
                break;
                
            case 'mark_read':
                markMessagesRead($input);
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

function getMessages() {
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
    
    $messages = $functions->getMatchMessages($match_id);
    
    // Mark messages as read
    $functions->markMessagesAsRead($match_id, $user_id);
    
    sendResponse($messages, 'Messages retrieved successfully');
}

function getConversations() {
    global $functions;
    
    $user_id = $_SESSION['user_id'];
    $matches = $functions->getUserMatches($user_id);
    
    $conversations = [];
    
    foreach ($matches as $match) {
        $other_user = ($match['user1_id'] == $user_id) ? [
            'id' => $match['user2_id'],
            'name' => $match['user2_first_name'] . ' ' . $match['user2_last_name'],
            'username' => $match['user2_username']
        ] : [
            'id' => $match['user1_id'],
            'name' => $match['user1_first_name'] . ' ' . $match['user1_last_name'],
            'username' => $match['user1_username']
        ];
        
        // Get last message
        $last_message = $functions->getLastMessage($match['id']);
        
        // Get unread count
        $unread_count = $functions->getUnreadMessageCount($match['id'], $user_id);
        
        $conversations[] = [
            'match_id' => $match['id'],
            'other_user' => $other_user,
            'status' => $match['status'],
            'teach_skill' => $match['teach_skill'],
            'learn_skill' => $match['learn_skill'],
            'last_message' => $last_message,
            'unread_count' => $unread_count,
            'matched_at' => $match['matched_at']
        ];
    }
    
    sendResponse($conversations, 'Conversations retrieved successfully');
}

function getUnreadCount() {
    global $functions;
    
    $user_id = $_SESSION['user_id'];
    $unread_count = $functions->getTotalUnreadMessages($user_id);
    
    sendResponse(['unread_count' => $unread_count], 'Unread count retrieved');
}

function sendMessage($input) {
    global $functions;
    
    validateRequired($input, ['match_id', 'content']);
    
    $match_id = $input['match_id'];
    $content = trim($input['content']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($content)) {
        sendError('Message content cannot be empty');
    }
    
    // Verify user has access to this match
    $match = $functions->getMatchDetails($match_id);
    
    if (!$match || ($match['user1_id'] != $user_id && $match['user2_id'] != $user_id)) {
        sendError('Access denied to this match', 403);
    }
    
    if ($match['status'] !== 'accepted' && $match['status'] !== 'completed') {
        sendError('Cannot send messages to pending or rejected matches');
    }
    
    // Check message length
    if (strlen($content) > 1000) {
        sendError('Message too long (max 1000 characters)');
    }
    
    // Send message
    $message_id = $functions->sendMessage($match_id, $user_id, $content);
    
    if ($message_id) {
        // Create notification for the other user
        $other_user_id = ($match['user1_id'] == $user_id) ? $match['user2_id'] : $match['user1_id'];
        
        $functions->createNotification(
            $other_user_id,
            'message',
            'New Message',
            $_SESSION['first_name'] . ' sent you a message',
            $match_id
        );
        
        // Get the sent message details
        $message = $functions->getMessageById($message_id);
        
        sendResponse($message, 'Message sent successfully');
    } else {
        sendError('Failed to send message');
    }
}

function markMessagesRead($input) {
    global $functions;
    
    validateRequired($input, ['match_id']);
    
    $match_id = $input['match_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify user has access to this match
    $match = $functions->getMatchDetails($match_id);
    
    if (!$match || ($match['user1_id'] != $user_id && $match['user2_id'] != $user_id)) {
        sendError('Access denied to this match', 403);
    }
    
    $success = $functions->markMessagesAsRead($match_id, $user_id);
    
    if ($success) {
        sendResponse(null, 'Messages marked as read');
    } else {
        sendError('Failed to mark messages as read');
    }
}
?>