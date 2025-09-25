// SkillSwap Real-time Chat System
class SkillSwapChat {
    constructor() {
        this.socket = null;
        this.currentConversation = null;
        this.conversations = new Map();
        this.isConnected = false;
        this.pendingMessages = new Map();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeSocket();
        this.loadConversations();
        this.setupMessageHandlers();
    }

    // WebSocket Connection
    initializeSocket() {
        const token = localStorage.getItem('userToken');
        if (!token) return;

        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}/ws?token=${token}`;
        
        this.socket = new WebSocket(wsUrl);

        this.socket.onopen = () => {
            this.isConnected = true;
            this.updateConnectionStatus('connected');
            this.sendPendingMessages();
            console.log('WebSocket connected');
        };

        this.socket.onmessage = (event) => {
            this.handleMessage(JSON.parse(event.data));
        };

        this.socket.onclose = () => {
            this.isConnected = false;
            this.updateConnectionStatus('disconnected');
            console.log('WebSocket disconnected');
            // Attempt reconnect after 5 seconds
            setTimeout(() => this.initializeSocket(), 5000);
        };

        this.socket.onerror = (error) => {
            console.error('WebSocket error:', error);
            this.updateConnectionStatus('error');
        };
    }

    updateConnectionStatus(status) {
        const statusElement = document.getElementById('connectionStatus');
        if (statusElement) {
            statusElement.className = `connection-status ${status}`;
            statusElement.textContent = this.getStatusText(status);
        }
    }

    getStatusText(status) {
        const statusTexts = {
            connected: 'Online',
            disconnected: 'Offline',
            error: 'Connection Error'
        };
        return statusTexts[status] || 'Unknown';
    }

    // Message Handling
    handleMessage(data) {
        switch (data.type) {
            case 'message':
                this.receiveMessage(data);
                break;
            case 'conversation_update':
                this.updateConversation(data.conversation);
                break;
            case 'typing_indicator':
                this.handleTypingIndicator(data);
                break;
            case 'message_read':
                this.handleMessageRead(data);
                break;
            case 'user_online':
                this.updateUserOnlineStatus(data.userId, true);
                break;
            case 'user_offline':
                this.updateUserOnlineStatus(data.userId, false);
                break;
        }
    }

    receiveMessage(messageData) {
        const { conversationId, message, sender, timestamp } = messageData;
        
        // Add to conversations map
        if (!this.conversations.has(conversationId)) {
            this.loadConversation(conversationId);
        }

        const conversation = this.conversations.get(conversationId);
        conversation.messages.push({
            id: message.id,
            content: message.content,
            sender: sender,
            timestamp: new Date(timestamp),
            read: false
        });

        // Update UI
        if (this.currentConversation === conversationId) {
            this.displayMessage(messageData);
            this.markAsRead(conversationId);
        } else {
            this.showNotification(conversationId, messageData);
        }

        this.updateConversationList(conversationId);
    }

    // UI Management
    displayMessage(messageData) {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;

        const messageElement = this.createMessageElement(messageData);
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Update last message preview
        this.updateConversationPreview(messageData.conversationId, messageData.message.content);
    }

    createMessageElement(messageData) {
        const isCurrentUser = messageData.sender.id === window.skillSwapApp.currentUser.id;
        const messageClass = isCurrentUser ? 'message-outgoing' : 'message-incoming';

        return document.createElement('div');
        messageElement.className = `message ${messageClass}`;
        messageElement.innerHTML = `
            <div class="message-content">
                ${!isCurrentUser ? `
                    <img src="${messageData.sender.avatar}" alt="${messageData.sender.name}" class="message-avatar">
                ` : ''}
                <div class="message-bubble">
                    <div class="message-text">${this.escapeHtml(messageData.message.content)}</div>
                    <div class="message-time">${window.utils.formatRelativeTime(messageData.timestamp)}</div>
                    ${isCurrentUser ? `
                        <div class="message-status">
                            ${messageData.message.read ? '✓✓' : '✓'}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        return messageElement;
    }

    // Conversation Management
    async loadConversations() {
        try {
            const response = await window.skillSwapApp.apiCall('GET', 'api/chat/conversations.php');
            if (response.success) {
                this.conversations = new Map(response.data.map(conv => [conv.id, conv]));
                this.renderConversationList();
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    async loadConversation(conversationId) {
        try {
            const response = await window.skillSwapApp.apiCall('GET', `api/chat/messages.php?conversation_id=${conversationId}`);
            if (response.success) {
                this.conversations.set(conversationId, response.data);
                this.renderConversation(conversationId);
            }
        } catch (error) {
            console.error('Error loading conversation:', error);
        }
    }

    renderConversationList() {
        const listContainer = document.getElementById('conversationsList');
        if (!listContainer) return;

        listContainer.innerHTML = Array.from(this.conversations.values())
            .map(conversation => this.createConversationElement(conversation))
            .join('');
    }

    createConversationElement(conversation) {
        const lastMessage = conversation.messages[conversation.messages.length - 1];
        const unreadCount = conversation.messages.filter(msg => !msg.read && msg.sender.id !== window.skillSwapApp.currentUser.id).length;

        return `
            <div class="conversation-item" data-conversation-id="${conversation.id}">
                <img src="${conversation.participant.avatar}" alt="${conversation.participant.name}" class="conversation-avatar">
                <div class="conversation-info">
                    <div class="conversation-header">
                        <span class="conversation-name">${conversation.participant.name}</span>
                        <span class="conversation-time">${lastMessage ? window.utils.formatRelativeTime(lastMessage.timestamp) : ''}</span>
                    </div>
                    <div class="conversation-preview">
                        <span class="conversation-last-message">${lastMessage ? lastMessage.content : 'No messages yet'}</span>
                        ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    // Message Sending
    async sendMessage(conversationId, content) {
        if (!content.trim()) return;

        const messageData = {
            conversationId: conversationId,
            content: content.trim(),
            timestamp: new Date().toISOString()
        };

        // Optimistically add to UI
        this.displayMessage({
            ...messageData,
            sender: window.skillSwapApp.currentUser,
            message: { id: 'temp-' + Date.now(), content: content, read: false }
        });

        // Clear input
        const messageInput = document.getElementById('messageInput');
        if (messageInput) messageInput.value = '';

        if (this.isConnected) {
            this.socket.send(JSON.stringify({
                type: 'message',
                ...messageData
            }));
        } else {
            // Store for sending when connection resumes
            this.pendingMessages.set(Date.now(), messageData);
        }

        // Also send via HTTP for reliability
        try {
            await window.skillSwapApp.apiCall('POST', 'api/chat/send.php', messageData);
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    sendPendingMessages() {
        this.pendingMessages.forEach((message, id) => {
            this.socket.send(JSON.stringify({
                type: 'message',
                ...message
            }));
            this.pendingMessages.delete(id);
        });
    }

    // Typing Indicators
    sendTypingIndicator(conversationId, isTyping) {
        if (this.isConnected) {
            this.socket.send(JSON.stringify({
                type: 'typing_indicator',
                conversationId: conversationId,
                isTyping: isTyping
            }));
        }
    }

    handleTypingIndicator(data) {
        if (data.conversationId === this.currentConversation) {
            this.showTypingIndicator(data.userId, data.isTyping);
        }
    }

    showTypingIndicator(userId, isTyping) {
        const indicator = document.getElementById('typingIndicator');
        if (!indicator) return;

        if (isTyping) {
            indicator.style.display = 'block';
            indicator.textContent = `${this.getUserName(userId)} is typing...`;
        } else {
            indicator.style.display = 'none';
        }
    }

    // Message Status
    async markAsRead(conversationId) {
        try {
            await window.skillSwapApp.apiCall('POST', 'api/chat/read.php', {
                conversationId: conversationId
            });

            if (this.isConnected) {
                this.socket.send(JSON.stringify({
                    type: 'message_read',
                    conversationId: conversationId
                }));
            }
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }

    handleMessageRead(data) {
        const conversation = this.conversations.get(data.conversationId);
        if (conversation) {
            conversation.messages.forEach(msg => {
                if (msg.sender.id !== window.skillSwapApp.currentUser.id) {
                    msg.read = true;
                }
            });
            this.updateMessageStatuses(data.conversationId);
        }
    }

    // Event Listeners
    setupEventListeners() {
        // Message input
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', this.debounce(() => {
                this.sendTypingIndicator(this.currentConversation, true);
            }, 500));

            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage(this.currentConversation, messageInput.value);
                    this.sendTypingIndicator(this.currentConversation, false);
                }
            });

            messageInput.addEventListener('blur', () => {
                this.sendTypingIndicator(this.currentConversation, false);
            });
        }

        // Send button
        const sendButton = document.getElementById('sendButton');
        if (sendButton) {
            sendButton.addEventListener('click', () => {
                const messageInput = document.getElementById('messageInput');
                this.sendMessage(this.currentConversation, messageInput.value);
            });
        }

        // Conversation selection
        document.addEventListener('click', (e) => {
            const conversationItem = e.target.closest('.conversation-item');
            if (conversationItem) {
                const conversationId = conversationItem.dataset.conversationId;
                this.selectConversation(conversationId);
            }
        });

        // File upload
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target.files[0]);
            });
        }
    }

    setupMessageHandlers() {
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.currentConversation) {
                this.markAsRead(this.currentConversation);
            }
        });
    }

    // Utility Functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    getUserName(userId) {
        const conversation = this.conversations.get(this.currentConversation);
        if (conversation) {
            const participant = conversation.participants.find(p => p.id === userId);
            return participant ? participant.name : 'User';
        }
        return 'User';
    }

    // File Handling
    async handleFileUpload(file) {
        if (!file) return;

        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            window.skillSwapApp.showNotification('File size must be less than 10MB', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('conversationId', this.currentConversation);

        try {
            const response = await fetch('api/chat/upload.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('userToken')
                }
            });

            if (response.ok) {
                window.skillSwapApp.showNotification('File uploaded successfully', 'success');
            } else {
                throw new Error('File upload failed');
            }
        } catch (error) {
            window.skillSwapApp.showNotification('Error uploading file', 'error');
            console.error('Upload error:', error);
        }
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('chatInterface')) {
        window.skillSwapChat = new SkillSwapChat();
    }
});