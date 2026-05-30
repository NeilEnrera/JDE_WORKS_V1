// JDE Works Chat JavaScript
class JDEChat {
    constructor() {
        this.currentUser = 'Guest User';
        this.isConnected = false;
        this.typingTimer = null;
        this.isSendingTyping = false;
        this.messageCount = 0;
        this.lastMessageId = 0;
        this.lastMessageDate = null;
        this.apiBase = 'chat_api.php';

        this.initializeElements();
        this.injectReplyPreview();
        this.bindEvents();

        // Start async initialization
        this.init();
    }

    async init() {
        await this.initializeChat();
        this.loadUsers();
        await this.loadMessages();
        if (window.isLoggedIn) {
            this.startRealTimeConnection();
        }
    }

    initializeElements() {
        // Main elements
        this.chatMessages = document.getElementById('chatMessages');
        this.messageInput = document.getElementById('messageInput');
        this.sendButton = document.getElementById('sendMessage');
        this.userList = document.getElementById('userList');
        // These elements may not exist if user is logged in (shown in PHP dropdown instead)
        this.currentUserElement = document.getElementById('currentUser');
        this.userStatus = document.getElementById('userStatus');
        this.typingIndicator = document.getElementById('typingIndicator');
        this.charCount = document.querySelector('.char-count');

        // Action buttons
        this.clearChatBtn = document.getElementById('clearChat');
        this.notificationsBtn = document.getElementById('toggleNotifications');
        this.attachFileBtn = document.getElementById('attachFile');

        // Modal elements
        const clearChatModalEl = document.getElementById('clearChatModal');
        this.clearChatModal = clearChatModalEl ? new bootstrap.Modal(clearChatModalEl) : null;
        this.confirmClearBtn = document.getElementById('confirmClearChat');

        // File Modal elements (optional - may be removed)
        const fileUploadModalEl = document.getElementById('fileUploadModal');
        this.fileModal = fileUploadModalEl ? new bootstrap.Modal(fileUploadModalEl) : null;
        this.fileInput = document.getElementById('fileInput');
        this.filePreview = document.getElementById('filePreview');
        this.uploadFileBtn = document.getElementById('uploadFile');
    }

    injectReplyPreview() {
        const inputContainer = document.querySelector('.chat-input-container');
        if (inputContainer && !document.getElementById('replyPreview')) {
            const previewDiv = document.createElement('div');
            previewDiv.id = 'replyPreview';
            previewDiv.className = 'reply-preview';
            previewDiv.innerHTML = `
                <div class="reply-preview-content">
                    <span class="reply-preview-sender"></span>
                    <span class="reply-preview-text"></span>
                </div>
                <button class="reply-preview-close">&times;</button>
            `;
            inputContainer.insertBefore(previewDiv, inputContainer.firstChild);

            // Add close listener
            previewDiv.querySelector('.reply-preview-close').addEventListener('click', () => {
                this.closeReplyPreview();
            });
        }
    }

    bindEvents() {
        // Message input events
        this.messageInput.addEventListener('input', () => {
            this.handleInputChange();
            this.handleTyping();
        });
        this.messageInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.messageInput.addEventListener('blur', () => {
            this.isSendingTyping = false;
            if (this.typingHeartbeat) clearInterval(this.typingHeartbeat);
            this.sendTypingState(false);
        });

        // Send button
        this.sendButton.addEventListener('click', () => this.sendMessage());

        // Action buttons
        this.clearChatBtn.addEventListener('click', () => this.clearChat());
        if (this.confirmClearBtn) {
            this.confirmClearBtn.addEventListener('click', () => {
                this.clearMessages();
                if (this.clearChatModal) this.clearChatModal.hide();
            });
        }
        this.notificationsBtn.addEventListener('click', () => this.toggleNotifications());
        if (this.attachFileBtn) this.attachFileBtn.addEventListener('click', () => this.openFileModal());

        // File upload (optional elements)
        if (this.fileInput) this.fileInput.addEventListener('change', () => this.handleFileSelect());
        if (this.uploadFileBtn) this.uploadFileBtn.addEventListener('click', () => this.uploadFile());

    }

    async initializeChat() {
        try {
            // Get current user info from server
            const userInfo = await this.loadInitialUsers();
            if (userInfo.success) {
                this.currentUser = userInfo.user.name;
                this.currentUserId = userInfo.user.userID; // Store ID
                this.isEmployee = userInfo.user.isEmployee || false;

                // Only update element if it exists (for guest users)
                if (this.currentUserElement) {
                    this.currentUserElement.textContent = this.currentUser;
                }
                if (this.userStatus) {
                    this.userStatus.classList.add('online');
                }
            } else {
                // Fallback to guest user
                this.currentUser = 'Guest User';
                this.currentUserId = 0;
                if (this.currentUserElement) {
                    this.currentUserElement.textContent = this.currentUser;
                }
            }
        } catch (error) {
            console.error('Failed to get user info:', error);
            this.currentUser = 'Guest User';
            this.currentUserId = 0;
            if (this.currentUserElement) {
                this.currentUserElement.textContent = this.currentUser;
            }
        }
    }

    async loadInitialUsers() {
        try {
            const response = await fetch(`${this.apiBase}?action=get_user_info`);
            return await response.json();
        } catch (error) {
            console.error('Error fetching user info:', error);
            return { success: false };
        }
    }

    async loadUsers() {
        try {
            const response = await fetch(`${this.apiBase}?action=get_online_users`);
            const data = await response.json();

            if (data.success) {
                this.userList.innerHTML = '';
                data.users.forEach(user => {
                    this.addUserToList(user);
                });
            } else {
                console.error('Failed to load users:', data.error);
                this.loadSampleUsers(); // Fallback to sample users
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.loadSampleUsers(); // Fallback to sample users
        }
    }

    loadSampleUsers() {
        const sampleUsers = [
            { name: 'JDE Support', role: 'Admin', status: 'online', avatar: 'JS' },
            { name: 'Tailor Master', role: 'Expert', status: 'online', avatar: 'TM' },
            { name: 'Order Manager', role: 'Staff', status: 'away', avatar: 'OM' },
            { name: 'Customer Service', role: 'Staff', status: 'online', avatar: 'CS' }
        ];

        this.userList.innerHTML = '';
        sampleUsers.forEach(user => {
            this.addUserToList(user);
        });
    }

    addUserToList(user) {
        const userElement = document.createElement('div');
        userElement.className = 'user-item';
        const avatar = this.getAvatar(user.name);
        userElement.innerHTML = `
            <div class="user-avatar ${user.role === 'Admin' ? 'admin' : ''}">
                ${avatar}
                <div class="user-status ${user.status}"></div>
            </div>
            <div class="user-info">
                <div class="user-name">${user.name}</div>
                <div class="user-role">${user.role}</div>
            </div>
        `;

        userElement.addEventListener('click', (event) => {
            this.selectUser(user, event);
        });

        this.userList.appendChild(userElement);
    }

    selectUser(user, event) {
        // Highlight selected user
        document.querySelectorAll('.user-item').forEach(item => {
            item.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');

        // In a real app, this would start a private conversation
        console.log(`Starting conversation with ${user.name}`);
    }

    async loadMessages() {
        try {
            // Use cache-buster to ensure we get the latest messages
            const timestamp = new Date().getTime();
            const response = await fetch(`${this.apiBase}?action=get_messages&limit=50&_t=${timestamp}`);
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                // Clear welcome message
                const welcomeMessage = this.chatMessages.querySelector('.welcome-message');
                if (welcomeMessage) {
                    welcomeMessage.remove();
                }

                // Add messages
                data.messages.forEach(msg => {
                    this.addMessageFromAPI(msg);
                });

                // Update last message ID
                if (data.messages.length > 0) {
                    this.lastMessageId = data.messages[data.messages.length - 1].id;
                    localStorage.setItem('jde_last_msg_id', this.lastMessageId);
                }

                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    handleInputChange() {
        const length = this.messageInput.value.length;
        this.charCount.textContent = `${length}/500`;

        // Enable/disable send button
        this.sendButton.disabled = length === 0;

        // Change color based on character count
        if (length > 450) {
            this.charCount.style.color = '#c7232c';
        } else if (length > 400) {
            this.charCount.style.color = '#ffc107';
        } else {
            this.charCount.style.color = '#a6a6a6';
        }
    }

    handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            this.sendMessage();
        }
    }


    handleTyping() {
        if (!this.isSendingTyping) {
            this.isSendingTyping = true;
            this.sendTypingState(true);

            if (this.typingHeartbeat) clearInterval(this.typingHeartbeat);
            this.typingHeartbeat = setInterval(() => {
                if (this.isSendingTyping) {
                    this.sendTypingState(true);
                } else {
                    clearInterval(this.typingHeartbeat);
                }
            }, 2000);
        }

        clearTimeout(this.typingTimer);
        this.typingTimer = setTimeout(() => {
            this.isSendingTyping = false;
            if (this.typingHeartbeat) clearInterval(this.typingHeartbeat);
            this.sendTypingState(false);
        }, 1500);
    }

    sendTypingState(isTyping) {
        fetch(this.apiBase, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set_typing', isTyping })
        }).catch(() => { });
    }

    showAdminTyping(name) {
        if (this.typingIndicator) {
            const nameEl = this.typingIndicator.querySelector('.typing-name');
            if (nameEl) nameEl.textContent = `${name} is typing...`;
            this.typingIndicator.classList.add('active');
        }
    }

    hideTypingIndicator() {
        if (this.typingIndicator) {
            this.typingIndicator.classList.remove('active');
        }
    }

    async sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;

        // Check for pending reply
        let finalMessage = message;
        if (this.currentReply) {
            finalMessage = `> "${this.currentReply.text}" - ${this.currentReply.sender}\n\n${message}`;
            this.closeReplyPreview();
        }

        // Add message immediately to UI for instant feedback
        // Use a temporary ID for tracking if needed, though raw text is primary match for optimistic
        this.addMessageFromAPI({
            message: finalMessage,
            sender: this.currentUser,
            userID: this.currentUserId,
            timeSent: new Date().toISOString()
        }, true); // true = optimistic

        const messageText = finalMessage; // Store for error handling
        this.messageInput.value = '';
        this.handleInputChange();
        // Clear typing state
        this.isSendingTyping = false;
        if (this.typingHeartbeat) clearInterval(this.typingHeartbeat);
        this.sendTypingState(false);
        if (this.typingTimer) clearTimeout(this.typingTimer);
        this.hideTypingIndicator();

        // Re-enable send button immediately for better UX
        this.sendButton.disabled = false;

        // Send to server in background (non-blocking)
        fetch(this.apiBase, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'send_message',
                message: messageText
            })
        })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                // Try to parse JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned invalid response. Please check your database connection.');
                }
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);

                // Remove the optimistic message
                if (optimisticMessage) optimisticMessage.remove();

                // Check if error is due to Admin session
                if (error.message && error.message.includes('Target customer required')) {
                    alert('You appear to be logged in as an Administrator in another tab. \n\nPlease refresh the page to switch to the Admin Dashboard.');
                    location.reload();
                    return;
                }

                // Show error to user
                this.addSystemMessage(`Error: ${error.message}. Please try again.`);
            });
    }

    addMessageFromAPI(msgData, isOptimistic = false) {
        // Remove welcome message if it exists
        const welcomeMessage = this.chatMessages.querySelector('.welcome-message');
        if (welcomeMessage) {
            welcomeMessage.remove();
        }

        // Standardize text - MUST BE DEFINED FIRST before using in debug logs
        const messageText = (msgData.message || '').trim();
        const normalizeText = (text) => (text || '').replace(/\s+/g, '').trim();
        const normalizedMessageText = normalizeText(messageText);

        // Determine attribution (Customer view)
        // A message is from the current user ONLY if IDs match AND the "employee-ness" matches.
        // This prevents Customer #1 from seeing Admin #1's messages as "Sent".
        const isCurrentUser = (msgData.userID == this.currentUserId) && (!!msgData.isAdmin === !!this.isEmployee);
        const isAdminMessage = !!msgData.isAdmin;

        // CHECK 1: ID Match (if available)
        if (msgData.id) {
            const existingMsg = this.chatMessages.querySelector(`[data-message-id="${msgData.id}"]`);
            if (existingMsg) {
                return; // Already exists
            }
        }


        // Proactive: If we receive a message from the other person, they aren't typing anymore
        if (!isCurrentUser) {
            this.hideTypingIndicator();
        }

        // CHECK 2: Resolve Optimistic Updates
        const allMessages = this.chatMessages.querySelectorAll('.message');
        for (let existingMsg of allMessages) {
            const existingRawText = existingMsg.getAttribute('data-raw-text');
            const existingId = existingMsg.getAttribute('data-message-id');
            const existingIsFromUser = existingMsg.classList.contains('sent');

            // Only compare relevant messages (sent vs sent, received vs received)
            if (existingIsFromUser === isCurrentUser) {
                const isMatch = normalizeText(existingRawText) === normalizedMessageText;

                if (isMatch) {
                    // If existing is optimistic (no ID) and new one has ID from server, replace it
                    if (!existingId && msgData.id) {
                        existingMsg.remove();
                        break; // Break and allow new authenticated message to be added
                    }
                }
            }
        }

        const messageElement = document.createElement('div');
        messageElement.className = `message ${isCurrentUser ? 'sent' : 'received'}`;
        if (isAdminMessage) {
            messageElement.classList.add('message-admin');
        }

        // Store metadata
        messageElement.setAttribute('data-raw-text', messageText);
        if (msgData.id) {
            messageElement.setAttribute('data-message-id', msgData.id);
        }
        if (msgData.userID) {
            messageElement.setAttribute('data-user-id', msgData.userID);
        }

        const time = msgData.timeSent ? new Date(msgData.timeSent).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        }) : this.getCurrentTime();

        const avatar = this.getAvatar(msgData.sender || 'User');

        // Check for reply pattern
        // Pattern: > "Quote" - Sender\n\nMessage
        // Robust regex using [\s\S]*? to handle newlines in the quoted text
        const replyMatch = messageText.match(/^> "([\s\S]*?)" - (.*?)\n\n([\s\S]*)$/);
        let displayMessage = messageText;
        let replyHeader = '';

        if (replyMatch) {
            const quotedText = replyMatch[1];
            const originalSender = replyMatch[2];
            displayMessage = replyMatch[3]; // Show only the new part

            // Construct reply header text
            let replyText = '';
            if (isCurrentUser) {
                // I sent this reply
                replyText = `<strong>You</strong> replied to <strong>${originalSender}</strong>`;
            } else {
                // Someone else sent this reply
                if (originalSender === this.currentUser) {
                    replyText = `<strong>${msgData.sender || 'User'}</strong> replied to <strong>You</strong>`;
                } else {
                    replyText = `<strong>${msgData.sender || 'User'}</strong> replied to <strong>${originalSender}</strong>`;
                }
            }

            replyHeader = `
                <div class="message-reply">
                    <i class="fas fa-reply reply-icon"></i>
                    ${replyText}
                </div>
            `;

            // Add quote box to message content
            displayMessage = `<div class="reply-quote-box">${quotedText}</div>${displayMessage}`;
        }

        messageElement.innerHTML = `
            <div class="message-avatar">${avatar}</div>
            <div class="message-content-wrapper">
                ${replyHeader}
                <div class="message-bubble-row">
                    <div class="message-content">
                        <p class="message-text">${this.formatMessage(displayMessage)}</p>
                        <div class="message-meta">
                            <span class="message-time">${time}</span>
                            ${isCurrentUser ? '<span class="message-status">✓✓</span>' : ''}
                        </div>
                    </div>
                    <div class="message-actions">
                        <button class="reply-btn" title="Reply to this message" aria-label="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add reply button listener
        const replyBtn = messageElement.querySelector('.reply-btn');
        if (replyBtn) {
            replyBtn.addEventListener('click', () => {
                const cleanText = messageText.replace(/> ".*?" - .*?\n\n/s, '');
                this.replyToMessage(cleanText, msgData.sender || 'User');
            });
        }

        // --- Date Separator ---
        const msgDate = msgData.timeSent ? new Date(msgData.timeSent) : new Date();
        const msgDateStr = msgDate.toDateString();
        if (this.lastMessageDate !== msgDateStr) {
            this.lastMessageDate = msgDateStr;
            const today = new Date().toDateString();
            const yesterday = new Date(Date.now() - 86400000).toDateString();
            let label = msgDateStr === today ? 'Today' : msgDateStr === yesterday ? 'Yesterday' : msgDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const separator = document.createElement('div');
            separator.className = 'date-separator';
            separator.innerHTML = `<span>${label}</span>`;
            this.chatMessages.appendChild(separator);
        }

        this.chatMessages.appendChild(messageElement);
        this.scrollToBottom();
        this.messageCount++;
    }

    // Deprecated addMessage in favor of addMessageFromAPI
    addMessage(text, sender, isAdmin = false, time = null) {
        this.addMessageFromAPI({
            message: text,
            sender: sender,
            isAdmin: isAdmin,
            timeSent: time ? new Date().toISOString() : new Date().toISOString() // Approximate
        });
    }

    addSystemMessage(text) {
        const messageElement = document.createElement('div');
        messageElement.className = 'message system';
        messageElement.innerHTML = `
            <div class="message-content" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #1976d2; margin: 0 auto; text-align: center; max-width: 80%;">
                <p class="message-text" style="margin: 0; font-style: italic;">${text}</p>
                <div class="message-meta" style="margin-top: 5px;">
                    <span class="message-time">${this.getCurrentTime()}</span>
                </div>
            </div>
        `;

        this.chatMessages.appendChild(messageElement);
        this.scrollToBottom();
    }

    formatMessage(text) {
        // Basic formatting for links and line breaks
        return text
            .replace(/\n/g, '<br>')
            .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color: inherit; text-decoration: underline;">$1</a>');
    }

    getAvatar(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    }

    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    scrollToBottom() {
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }

    simulateResponse(userMessage) {
        // Disabled - using real admin responses only
    }

    clearMessages() {
        // Keep only the welcome message structure
        this.chatMessages.innerHTML = `
            <div class="welcome-message">
                <div class="welcome-content">
                    <i class="fas fa-scissors"></i>
                    <h3>Welcome to JDE Works Chat!</h3>
                    <p>Our expert tailors are here to help you with:</p>
                    <ul>
                        <li>Custom uniform orders</li>
                        <li>Size measurements and fittings</li>
                        <li>Order status updates</li>
                        <li>General inquiries</li>
                    </ul>
                    <p>Start typing below to begin your conversation!</p>
                </div>
            </div>
        `;
    }

    clearChat() {
        if (this.clearChatModal) {
            this.clearChatModal.show();
        } else {
            // Re-attempt initialization in case it was missed due to timing
            const clearChatModalEl = document.getElementById('clearChatModal');
            if (clearChatModalEl && typeof bootstrap !== 'undefined') {
                this.clearChatModal = new bootstrap.Modal(clearChatModalEl);
                this.clearChatModal.show();
            } else {
                // Final fallback if all else fails
                if (confirm('Are you sure you want to clear the chat history?')) {
                    this.clearMessages();
                }
            }
        }
    }

    toggleNotifications() {
        const icon = this.notificationsBtn.querySelector('i');
        if (icon.classList.contains('fa-bell')) {
            icon.classList.remove('fa-bell');
            icon.classList.add('fa-bell-slash');
            this.notificationsBtn.title = 'Enable Notifications';
        } else {
            icon.classList.remove('fa-bell-slash');
            icon.classList.add('fa-bell');
            this.notificationsBtn.title = 'Disable Notifications';
        }
    }

    openFileModal() {
        this.fileModal.show();
    }

    handleFileSelect() {
        const files = this.fileInput.files;
        if (files.length > 0) {
            this.filePreview.innerHTML = '';
            this.filePreview.classList.add('active');

            Array.from(files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <i class="fas fa-file file-icon"></i>
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${this.formatFileSize(file.size)}</div>
                `;
                this.filePreview.appendChild(fileItem);
            });
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    uploadFile() {
        const files = this.fileInput.files;
        if (files.length > 0) {
            // In a real app, this would upload to server
            Array.from(files).forEach(file => {
                this.addMessage(`📎 ${file.name} (${this.formatFileSize(file.size)})`, this.currentUser, false);
            });

            this.fileModal.hide();
            this.fileInput.value = '';
            this.filePreview.innerHTML = '';
            this.filePreview.classList.remove('active');
        }
    }



    startRealTimeConnection() {
        // Close existing connection if any
        if (this.eventSource) {
            this.eventSource.close();
        }

        // Start Server-Sent Events connection
        this.eventSource = new EventSource(`chat_stream.php?lastMessageId=${this.lastMessageId}`);

        // Handle connection event
        this.eventSource.addEventListener('connected', (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log('Connected to real-time chat:', data.message);
            } catch (error) {
                console.error('Error parsing connected event:', error);
            }
        });

        // Handle ping events to keep connection alive and monitor health
        this.eventSource.addEventListener('ping', (event) => {
            this.lastPing = Date.now();
            if (this.reconnectTimeout) {
                clearTimeout(this.reconnectTimeout);
                this.reconnectTimeout = null;
            }
        });

        // Handle new message events
        this.eventSource.addEventListener('new_message', (event) => {
            try {
                const msg = JSON.parse(event.data);
                this.addMessageFromAPI(msg);
                this.lastMessageId = msg.id;
                localStorage.setItem('jde_last_msg_id', msg.id);
            } catch (error) {
                console.error('Error parsing new_message event:', error);
            }
        });

        // Handle online users updates
        this.eventSource.addEventListener('online_users', (event) => {
            try {
                const data = JSON.parse(event.data);
                // Update online users list if needed
                if (data.users) {
                    this.loadUsers();
                }
            } catch (error) {
                console.error('Error parsing online_users event:', error);
            }
        });

        // Handle typing status updates
        this.eventSource.addEventListener('typing_status', (event) => {
            try {
                const data = JSON.parse(event.data);
                const adminTyping = (data.typing || []).find(t => t.type === 'admin');

                if (adminTyping) {
                    this.showAdminTyping(adminTyping.name);
                } else {
                    this.hideTypingIndicator();
                }
            } catch (error) {
                console.error('Error parsing typing_status event:', error);
            }
        });

        this.eventSource.onerror = (error) => {
            console.error('SSE connection error:', error);
            this.isConnected = false;

            // Immediately close the failing connection
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }

            // Attempt to reconnect after a short delay (backoff)
            if (!this.reconnectTimeout) {
                this.reconnectTimeout = setTimeout(() => {
                    this.reconnectTimeout = null;
                    this.startRealTimeConnection();
                }, 3000);
            }
        };
    }

    replyToMessage(messageText, senderName) {
        if (!this.messageInput) return;

        // Strip HTML if any (simple)
        const temp = document.createElement('div');
        temp.innerHTML = messageText;
        const text = temp.textContent || temp.innerText || '';

        // Store reply context
        this.currentReply = {
            text: text,
            sender: senderName
        };

        // Show preview
        const preview = document.getElementById('replyPreview');
        if (preview) {
            preview.querySelector('.reply-preview-sender').textContent = `Replying to ${senderName}`;
            preview.querySelector('.reply-preview-text').textContent = text;
            preview.classList.add('active');
        }

        this.messageInput.focus();

        // Trigger input event logic if any
        if (this.handleInputChange) this.handleInputChange();
    }

    closeReplyPreview() {
        this.currentReply = null;
        const preview = document.getElementById('replyPreview');
        if (preview) {
            preview.classList.remove('active');
        }
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const chat = new JDEChat();
});

// Add some utility functions for future real-time implementation
class ChatWebSocket {
    constructor(url) {
        this.url = url;
        this.socket = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }

    connect() {
        try {
            this.socket = new WebSocket(this.url);

            this.socket.onopen = () => {
                console.log('Connected to chat server');
                this.reconnectAttempts = 0;
            };

            this.socket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
            };

            this.socket.onclose = () => {
                console.log('Disconnected from chat server');
                this.attemptReconnect();
            };

            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Failed to connect to chat server:', error);
        }
    }

    sendMessage(message) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify(message));
        }
    }

    handleMessage(data) {
        // Handle different message types
        switch (data.type) {
            case 'message':
                // Add message to chat
                break;
            case 'user_join':
                // Add user to list
                break;
            case 'user_leave':
                // Remove user from list
                break;
            case 'typing':
                // Show typing indicator
                break;
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => {
                console.log(`Attempting to reconnect... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                this.connect();
            }, 2000 * this.reconnectAttempts);
        }
    }

    disconnect() {
        if (this.socket) {
            this.socket.close();
        }
    }
}
