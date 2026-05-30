// JDE Works Admin Chat JavaScript
class AdminChat {
    constructor() {
        this.currentCustomer = null;
        this.isConnected = false;
        this.typingTimer = null;
        this.isSendingTyping = false;
        this.messageCount = 0;
        this.lastMessageId = 0;
        this.eventSource = null;
        this.apiBase = (window.JDE_CHAT_BASE || '') + 'chat_api.php';
        this.currentReply = null;
        this.onlineUserIds = []; // Track IDs of users currently online
        this.pendingMessages = new Map(); // Track optimistic messages

        this.initializeElements();
        this.bindEvents();
        this.injectReplyPreview();
        this.initializeAdminChat();
        this.initialize(); // Call the new initialize method

        // Expose a method for global SSE to update online status
        window.updateOnlineUsers = (data) => {
            if (data && data.users) {
                this.handleOnlineUsersUpdate(data.users);
            }
        };
    }

    // New initialize method as per instruction
    async initialize() {
        // 1. First fetch latest chat ID to prevent historical surges
        try {
            const resp = await fetch(`${this.apiBase}?action=get_max_chat_id`);
            const data = await resp.json();
            if (data.success && data.maxChatID) {
                this.lastMessageId = data.maxChatID;
                console.log('Chat system initialized at ID:', this.lastMessageId);
            }
        } catch (e) { console.error('Initial ID fetch failed', e); }

        // 2. Load lists and start connection
        await this.loadCustomers();
        this.loadMessages();
        this.startRealTimeConnection();

        // 3. Check URL parameters for customer selection
        const urlParams = new URLSearchParams(window.location.search);
        const targetCustomerId = urlParams.get('customerID');
        if (targetCustomerId) {
            const targetCustomer = document.querySelector(`.customer-item[data-customer-id="${targetCustomerId}"]`);
            if (targetCustomer) {
                targetCustomer.click();
            }
        }
    }

    initializeElements() {
        // Main elements
        this.chatMessages = document.getElementById('chatMessages');
        this.messageInput = document.getElementById('messageInput');
        this.sendButton = document.getElementById('sendMessage');
        this.customerList = document.getElementById('customerList');
        this.adminName = document.getElementById('adminName');

        // Action buttons
        this.customerSearch = document.getElementById('customerSearch');
        this.markAsReadBtn = document.getElementById('markAsRead');
        this.customerInfoBtn = document.getElementById('customerInfo');

        // Modals
        const customerInfoModalEl = document.getElementById('customerInfoModal');
        this.customerInfoModal = customerInfoModalEl ? new bootstrap.Modal(customerInfoModalEl) : null;
        
        const notificationModalEl = document.getElementById('notificationModal');
        this.notificationModal = notificationModalEl ? new bootstrap.Modal(notificationModalEl) : null;

        const clearAllModalEl = document.getElementById('clearAllMessagesModal');
        this.clearAllMessagesModal = clearAllModalEl ? new bootstrap.Modal(clearAllModalEl) : null;
        this.confirmClearAllBtn = document.getElementById('confirmClearAll');

        // Quick responses
        this.quickResponses = document.querySelectorAll('.quick-response');
        this.charCountText = document.getElementById('charCountText');
    }

    bindEvents() {
        // Message input events
        this.messageInput.addEventListener('input', () => {
            this.handleInputChange();
            this.handleTyping();
        });
        this.messageInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.messageInput.addEventListener('blur', () => {
            if (!this.currentCustomer) return;
            this.isSendingTyping = false;
            if (this.typingHeartbeat) clearInterval(this.typingHeartbeat);
            this.sendTypingState(false);
        });

        // Send button
        this.sendButton.addEventListener('click', () => this.sendMessage());

        // Search
        if (this.customerSearch) {
            this.customerSearch.addEventListener('input', () => this.handleSearch());
        }

        // Action buttons
        if (this.markAsReadBtn) {
            this.markAsReadBtn.addEventListener('click', () => this.markAsRead());
        }
        if (this.customerInfoBtn) {
            this.customerInfoBtn.addEventListener('click', () => this.showCustomerInfo());
        }

        // Quick responses
        this.quickResponses.forEach(response => {
            response.addEventListener('click', () => {
                this.messageInput.value = response.dataset.response;
                this.handleInputChange();
            });
        });

        // Clear All Confirmation
        if (this.confirmClearAllBtn) {
            this.confirmClearAllBtn.addEventListener('click', () => {
                this.executeClearAllMessages();
            });
        }
    }

    injectReplyPreview() {
        // Fix: Use .chat-input-wrapper to match admin_chat.view.php
        const inputContainer = document.querySelector('.chat-input-wrapper');
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
            // Insert at the top of the input wrapper
            inputContainer.insertBefore(previewDiv, inputContainer.firstChild);

            // Add close listener
            previewDiv.querySelector('.reply-preview-close').addEventListener('click', () => {
                this.closeReplyPreview();
            });
        }
    }

    async initializeAdminChat() {
        try {
            // Ensure no customer is pre-selected on initialization
            this.currentCustomer = null;

            // Get admin user info from server
            const userInfo = await this.fetchAdminInfo();
            if (userInfo.success && userInfo.user) {
                this.adminId = userInfo.user.userID;
                this.adminName.textContent = userInfo.user.name || 'Admin User';
                this.adminRole = userInfo.user.role || 'Store Manager'; // Capture role for attribution
            } else {
                // Fallback to default admin ID
                this.adminId = 1;
                this.adminName.textContent = 'Admin User';
                this.adminRole = 'Store Manager';
            }

            // Periodically refresh customer list as a fallback
            setInterval(() => this.loadCustomers(), 30000);
        } catch (error) {
            console.error('Failed to initialize admin chat:', error);
            // Fallback to default admin ID
            this.adminId = 1;
            this.adminName.textContent = 'Admin User';
        }
    }

    async fetchAdminInfo() {
        try {
            const response = await fetch(`${this.apiBase}?action=get_user_info`);
            return await response.json();
        } catch (error) {
            console.error('Error fetching admin info:', error);
            return { success: false };
        }
    }

    async loadCustomers() {
        try {
            const response = await fetch(`${this.apiBase}?action=get_online_users`);
            const data = await response.json();

            if (data.success) {
                this.customerList.innerHTML = '';
                if (data.users && data.users.length > 0) {
                    data.users.forEach(user => {
                        this.addCustomerToList(user);
                    });
                } else {
                    this.customerList.innerHTML = `
                        <div class="text-center p-3 text-muted">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <p>No customers available</p>
                            <small>Customers will appear here when they start chatting</small>
                        </div>
                    `;
                }
            } else {
                console.error('Failed to load customers:', data.error);
                this.customerList.innerHTML = `
                    <div class="text-center p-3 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error loading customers</p>
                        <small>Please refresh the page</small>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading customers:', error);
            this.customerList.innerHTML = `
                <div class="text-center p-3 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Connection error</p>
                    <small>Please check your internet connection</small>
                </div>
            `;
        }
    }

    addCustomerToList(customer) {
        const customerElement = document.createElement('div');
        customerElement.className = 'customer-item';
        customerElement.dataset.customerId = customer.userID;

        let lastMessagePreview = (customer.lastMessage || '').trim();
        if (lastMessagePreview.startsWith('> "')) {
            // Extract the actual reply content (the last part after newlines)
            const parts = lastMessagePreview.split(/[\r\n]+/);
            if (parts.length > 0) {
                lastMessagePreview = parts[parts.length - 1].trim();
            }
        }

        const unreadCount = customer.unreadCount || 0;
        const isOnline = (customer.status || '').toLowerCase() === 'online';

        // Build initials avatar
        const initials = customer.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

        customerElement.innerHTML = `
            <div class="customer-avatar">${initials}</div>
            <div class="customer-info">
                <div class="customer-name">${customer.name}</div>
                ${lastMessagePreview ? `<div class="customer-last-message">${lastMessagePreview}</div>` : '<div class="customer-last-message">No messages yet</div>'}
            </div>
            <div class="customer-meta">
                <span class="status-dot ${isOnline ? 'status-online' : 'status-offline'}" 
                      style="${unreadCount > 0 ? 'display: none;' : ''}"
                      title="${isOnline ? 'Online' : 'Offline'}"></span>
                ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
            </div>
        `;

        customerElement.addEventListener('click', () => {
            this.selectCustomer(customer);
        });

        this.customerList.appendChild(customerElement);
    }

    selectCustomer(customer) {
        // Update active customer
        document.querySelectorAll('.customer-item').forEach(item => {
            item.classList.remove('active');
        });

        const customerItem = document.querySelector(`[data-customer-id="${customer.userID}"]`);
        if (customerItem) {
            customerItem.classList.add('active');

            // Clear unread badge and restore status dot
            const badge = customerItem.querySelector('.unread-badge');
            if (badge) badge.remove();

            const dot = customerItem.querySelector('.status-dot');
            if (dot) dot.style.display = ''; // Restore default visibility
        }

        this.currentCustomer = customer;

        // Update header
        const nameHeader = document.getElementById('currentCustomerName');
        const descHeader = document.getElementById('currentCustomerDesc');

        if (nameHeader) nameHeader.textContent = customer.name;
        if (descHeader) descHeader.textContent = `Chatting with ${customer.name} (${customer.role})`;

        // Load messages for this customer
        this.loadMessages();

        // Mark messages as read
        this.markAsRead();

        // RESET TYPING STATE for the new customer
        if (this.typingHeartbeat) clearInterval(this.typingHeartbeat);
        if (this.typingTimer) clearTimeout(this.typingTimer);
        this.isSendingTyping = false;
        this.hideTypingIndicator();
    }

    async loadMessages() {
        if (!this.currentCustomer) return;

        try {
            const response = await fetch(`${this.apiBase}?action=get_messages&customerID=${this.currentCustomer.userID}&limit=50`);
            const data = await response.json();

            if (data.success) {
                // Clear existing messages ALWAYS
                this.chatMessages.innerHTML = '';

                // Clear welcome message
                const welcomeMessage = this.chatMessages.querySelector('.welcome-message');
                if (welcomeMessage) {
                    welcomeMessage.remove();
                }

                if (data.messages && data.messages.length > 0) {
                    // Filter messages for current customer (Extra safety)
                    const customerMessages = data.messages.filter(msg =>
                        Number(msg.customerID) === Number(this.currentCustomer.userID)
                    );

                    // Add messages
                    customerMessages.forEach(msg => {
                        this.addMessageFromAPI(msg);
                    });
                } else {
                    // Show "No messages yet" placeholder
                    this.chatMessages.innerHTML = '<div class="text-center text-muted mt-5">No messages yet. Start the conversation!</div>';
                }
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    addMessageFromAPI(msgData) {
        // Remove "No messages yet" placeholder if it exists
        const placeholder = this.chatMessages.querySelector('.text-muted');
        if (placeholder && placeholder.textContent.includes('No messages yet')) {
            placeholder.remove();
        }

        // Determine if message is from Admin (Sent) or Customer (Received)
        // Trust the server's isAdmin flag primarily
        let isAdmin = false;
        if (typeof msgData.isAdmin !== 'undefined') {
            isAdmin = (msgData.isAdmin === true || msgData.isAdmin === 1 || String(msgData.isAdmin).toLowerCase() === 'true');
        } else if (msgData.employeeID) {
            isAdmin = true;
        } else if (msgData.isFromEmployee) {
            isAdmin = true;
        }

        // Update the object for consistency
        msgData.isAdmin = isAdmin;

        const messageText = (msgData.message || '').trim();
        const normalizeText = (text) => (text || '').replace(/\s+/g, '').trim();
        const normalizedMessageText = normalizeText(messageText);

        // CRITICAL: Check if message already exists by ID (for messages from server)
        if (msgData.id) {
            const existingMsg = this.chatMessages.querySelector(`[data-message-id="${msgData.id}"]`);
            if (existingMsg) {
                // Message already exists, don't add duplicate
                return;
            }
        }

        // CRITICAL: Check ALL messages to resolve optimistic updates.
        // We use normalized text comparison to catch "close enough" matches (ignoring whitespace differences)
        const allMessages = this.chatMessages.querySelectorAll('.message');
        for (let existingMsg of allMessages) {
            // Get text to compare
            const existingRawText = existingMsg.getAttribute('data-raw-text');
            const messageTextEl = existingMsg.querySelector('.message-text');
            const existingText = messageTextEl ? messageTextEl.textContent.trim() : '';
            const normalizedExisting = normalizeText(existingRawText || existingText);

            const isMatch = normalizedExisting === normalizedMessageText;

            if (isMatch) {
                const existingIsAdmin = existingMsg.classList.contains('message-admin') || existingMsg.classList.contains('sent');

                // If both are admin messages or both are customer messages, check by ID
                if (existingIsAdmin === isAdmin) {
                    const existingId = existingMsg.getAttribute('data-message-id');

                    // If existing has no ID but this one does, replace it
                    if (!existingId && msgData.id) {
                        existingMsg.remove();
                        // IMPORTANT: We found our optimistic match and removed it.
                        // We break the loop to allow the new authenticated message to flow down and be created.
                        break;
                    }
                }
            }
        }

        // Proactive: If we receive a message from the customer, they aren't typing anymore
        if (!isAdmin) {
            this.hideTypingIndicator();
        }

        // CRITICAL: If we have a current customer, only show messages for that specific customer's conversation
        // This prevents "leaking" messages from other customers into the current view via SSE
        if (this.currentCustomer) {
            const msgCustomerID = Number(msgData.customerID || (isAdmin ? 0 : msgData.userID));
            if (msgCustomerID !== Number(this.currentCustomer.userID)) {
                return; // Skip messages for other customers
            }
        }

        // For admin messages, check if we already have this message (optimistic update)
        // This is a secondary check using the map, in case the DOM loop didn't catch it
        if (isAdmin) {
            // First check pending messages map
            if (this.pendingMessages.has(messageText)) {
                // This is a message we sent optimistically - replace it with the real one
                const pendingElement = this.pendingMessages.get(messageText);
                if (pendingElement && pendingElement.parentNode) {
                    // Remove the optimistic message
                    pendingElement.remove();
                }
                this.pendingMessages.delete(messageText);
            }
        }

        // CRITICAL: Final determination
        const finalIsAdmin = isAdmin; // We rely on our computed isAdmin which might have been forced true

        // Logic (Admin View):
        // - Admin message = 'sent' class (Right side)
        // - Customer message = 'received' class (Left side)
        const messageElement = document.createElement('div');
        messageElement.className = `message ${finalIsAdmin ? 'sent' : 'received'}`;

        if (finalIsAdmin) {
            messageElement.classList.add('message-admin');
        }

        // Store RAW text for robust duplicate checking
        messageElement.setAttribute('data-raw-text', messageText);

        // Add message ID as data attribute for duplicate detection
        if (msgData.id) {
            messageElement.setAttribute('data-message-id', msgData.id);
        }

        // Also add userID as data attribute for additional duplicate checking
        if (msgData.userID) {
            messageElement.setAttribute('data-user-id', msgData.userID);
        }

        // Ensure sender name is correct for admin messages
        const senderName = finalIsAdmin ? (msgData.sender || 'Admin User') : (msgData.sender || 'Customer');
        const avatar = this.getAvatar(senderName);
        const time = new Date(msgData.timeSent).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        // Check for reply pattern
        // Pattern: > "Quote" - Sender\n\nMessage
        const replyMatch = msgData.message.match(/^> "([\s\S]*?)" - (.*?)\n\n([\s\S]*)$/);
        let displayMessage = msgData.message;
        let replyHeader = '';

        if (replyMatch) {
            const quotedText = replyMatch[1];
            const originalSender = replyMatch[2];
            displayMessage = replyMatch[3]; // Show only the new part

            // Construct reply header text with BOLD names
            let replyText = '';
            if (finalIsAdmin) {
                // If I (Admin) am viewing a message I sent
                replyText = `<strong>You (${this.adminRole || 'Store Manager'})</strong> replied to <strong>${originalSender}</strong>`;
            } else {
                // If I am viewing a message from the customer
                replyText = `<strong>${senderName}</strong> replied to <strong>You</strong>`;
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
                            ${finalIsAdmin ? '<span class="message-status">✓✓</span>' : ''}
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
                const cleanText = msgData.message.replace(/> ".*?" - .*?\n\n/s, ''); // Remove existing quotes if nested
                this.replyToMessage(cleanText, senderName);
            });
        }

        this.chatMessages.appendChild(messageElement);
        this.scrollToBottom();

        // If this is an optimistic message (no ID), track it for replacement
        if (isAdmin && !msgData.id) {
            this.pendingMessages.set(messageText, messageElement);
            // Clean up after 10 seconds if message never gets an ID
            setTimeout(() => {
                if (this.pendingMessages.has(messageText)) {
                    this.pendingMessages.delete(messageText);
                }
            }, 10000);
        } else if (isAdmin && msgData.id) {
            // If this is a confirmed admin message with ID, make sure it's not in pending
            this.pendingMessages.delete(messageText);
        }
    }

    async sendMessage() {
        if (!this.currentCustomer) {
            this.showNotification('Please select a customer before sending a message.', 'No Customer Selected');
            return;
        }

        const message = this.messageInput.value.trim();
        if (!message) return;

        // Check for pending reply
        let finalMessage = message;
        if (this.currentReply) {
            finalMessage = `> "${this.currentReply.text}" - ${this.currentReply.sender}\n\n${message}`;
            this.closeReplyPreview();
        }

        // Add message immediately to UI for instant feedback
        this.addMessageFromAPI({
            message: finalMessage,
            sender: this.adminRole || 'Store Manager', // Use role for consistency with customer view
            isAdmin: true,
            customerID: this.currentCustomer.userID || this.currentCustomer.customerID,
            timeSent: new Date().toISOString()
        });
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
                message: messageText,
                customerID: this.currentCustomer.userID || this.currentCustomer.customerID,
                userID: this.currentCustomer.userID || this.currentCustomer.customerID,
                isAdmin: true
            })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                // alert(`Error: ${error.message}. Please try again.`); // Silent fail for background tasks, or handle more gracefully
            });
    }




    handleSearch() {
        const query = this.customerSearch.value.toLowerCase().trim();
        const customers = this.customerList.querySelectorAll('.customer-item');

        customers.forEach(item => {
            const name = item.querySelector('.customer-name').textContent.toLowerCase();
            const lastMsg = item.querySelector('.customer-last-message')?.textContent.toLowerCase() || '';
            const isMatch = name.includes(query) || lastMsg.includes(query);

            item.style.display = isMatch ? 'flex' : 'none';
        });

        // Show "no results" if everything is hidden
        const visibleCount = Array.from(customers).filter(c => c.style.display !== 'none').length;
        let noResultsMsg = this.customerList.querySelector('.no-results-msg');

        if (visibleCount === 0 && query !== '') {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results-msg text-center p-4 text-muted';
                noResultsMsg.innerHTML = '<i class="bi bi-search fa-2x mb-2 d-block"></i><p>No customers found matching "' + query + '"</p>';
                this.customerList.appendChild(noResultsMsg);
            } else {
                noResultsMsg.querySelector('p').textContent = 'No customers found matching "' + query + '"';
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }

    async markAsRead() {
        if (!this.currentCustomer) return;

        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_as_read',
                    customerID: this.currentCustomer.userID
                })
            });

            // Update UI to remove unread indicators and un-bold text
            const customerElement = document.querySelector(`[data-customer-id="${this.currentCustomer.userID}"]`);
            if (customerElement) {
                const badge = customerElement.querySelector('.unread-badge');
                if (badge) {
                    badge.remove();
                }

                const lastMsg = customerElement.querySelector('.customer-last-message');
                if (lastMsg) lastMsg.style.fontWeight = 'normal';
            }
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }

    showCustomerInfo() {
        if (!this.currentCustomer) {
            this.showNotification('You need to select a customer from the list to view their full details.', 'Select Customer First');
            return;
        }

        const customerInfoBody = document.getElementById('customerInfoBody');
        customerInfoBody.innerHTML = `
            <div class="text-center" style="padding: 10px 10px 5px;">
                <div style="width: 72px; height: 72px; background: #fff8eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #d6b25e; box-shadow: 0 10px 20px rgba(214, 178, 94, 0.15);">
                    <i class="bi bi-person-badge" style="font-size: 32px;"></i>
                </div>
                <h4 style="font-weight: 800; color: #0b2e46; font-family: 'Inter', sans-serif; margin-bottom: 5px; font-size: 22px;">${this.currentCustomer.name || 'Anonymous'}</h4>
                <p style="color: #94a3b8; font-size: 14px; margin-bottom: 25px;">${this.currentCustomer.role || 'Customer'} &bull; <span style="color: ${(this.currentCustomer.status || '').toLowerCase() === 'online' ? '#28a745' : '#6c757d'}; font-weight: 600; text-transform: capitalize;">${this.currentCustomer.status || 'Offline'}</span></p>
                
                <div style="background: #f8fafc; border-radius: 16px; padding: 20px; text-align: left; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                        <span style="color: #64748b; font-weight: 600; font-size: 13.5px;"><i class="bi bi-envelope me-2" style="color: #d6b25e;"></i>Username</span>
                        <span style="color: #0b2e46; font-weight: 700; font-size: 13.5px;">${this.currentCustomer.email || this.currentCustomer.username || 'Not Available'}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
                        <span style="color: #64748b; font-weight: 600; font-size: 13.5px;"><i class="bi bi-chat-dots me-2" style="color: #d6b25e;"></i>Messages</span>
                        <span style="color: #0b2e46; font-weight: 700; font-size: 13.5px;">${this.currentCustomer.messageCount || 0} exchanged</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #64748b; font-weight: 600; font-size: 13.5px;"><i class="bi bi-clock-history me-2" style="color: #d6b25e;"></i>Last Login</span>
                        <span style="color: #0b2e46; font-weight: 700; font-size: 13.5px;">${this.currentCustomer.lastLogin || 'Unknown'}</span>
                    </div>
                </div>
                
                <div style="margin-top: 25px;">
                    <button type="button" class="btn w-100" data-bs-dismiss="modal" style="background: #0b2e46; color: white; border-radius: 14px; padding: 14px; font-weight: 700; font-size: 15px; transition: all 0.3s ease; box-shadow: 0 8px 15px rgba(11, 46, 70, 0.2);">Close Details</button>
                </div>
            </div>
        `;

        this.customerInfoModal.show();
    }

    startRealTimeConnection() {
        // Close existing connection if any
        if (this.eventSource) {
            this.eventSource.close();
        }

        // Start Server-Sent Events connection (named events)
        const chatStreamBase = (window.JDE_CHAT_BASE || '');
        this.eventSource = new EventSource(`${chatStreamBase}chat_stream.php?lastMessageId=${this.lastMessageId}&room=general`);

        this.eventSource.addEventListener('connected', () => {
            this.isConnected = true;
        });

        this.eventSource.addEventListener('new_message', (event) => {
            try {
                const msg = JSON.parse(event.data);

                // 1. Update global message tracker
                if (msg.id) {
                    this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                }

                // 2. Determine relevance
                const msgCustomerID = Number(msg.customerID || (msg.isAdmin ? 0 : msg.userID));
                const isForCurrentChat = this.currentCustomer && msgCustomerID === Number(this.currentCustomer.userID);

                if (isForCurrentChat) {
                    // Add to current chat UI (deduplication happens inside addMessageFromAPI)
                    this.addMessageFromAPI(msg);
                } else if (!msg.isAdmin && msg.userID) {
                    // It's a message from another customer - show notification
                    this.handleIncomingNotification(msg);
                }

                // 3. Discovery: If customer not in list, refresh list
                if (!document.querySelector(`[data-customer-id="${msg.userID}"]`) && !msg.isAdmin) {
                    this.loadCustomers();
                }
            } catch (error) {
                console.error('Error parsing SSE message:', error);
            }
        });

        this.eventSource.addEventListener('online_users', (event) => {
            try {
                const payload = JSON.parse(event.data);
                this.handleOnlineUsersUpdate(payload.users);
            } catch (error) {
                console.error('Error parsing online_users event:', error);
            }
        });

        // Handle typing status updates
        this.eventSource.addEventListener('typing_status', (event) => {
            try {
                const data = JSON.parse(event.data);
                if (this.currentCustomer) {
                    const currentID = Number(this.currentCustomer.userID || this.currentCustomer.customerID);
                    const customerTyping = (data.typing || []).find(t =>
                        t.type === 'customer' && Number(t.customerID) === currentID
                    );

                    if (customerTyping) {
                        this.showTypingIndicator();
                    } else {
                        this.hideTypingIndicator();
                    }
                } else {
                    this.hideTypingIndicator();
                }
            } catch (error) {
                console.error('Error parsing typing_status event:', error);
            }
        });

        this.eventSource.onerror = (error) => {
            console.error('SSE connection error:', error);
            // Attempt to reconnect after 5 seconds
            setTimeout(() => {
                this.startRealTimeConnection();
            }, 5000);
        };
    }

    handleOnlineUsersUpdate(users) {
        this.onlineUserIds = (users || []).map(u => Number(u.userID));

        // Update all items in the list
        document.querySelectorAll('#customerList .customer-item').forEach(item => {
            const id = Number(item.dataset.customerId);
            const isOnline = this.onlineUserIds.includes(id);
            const dot = item.querySelector('.status-dot');

            if (dot) {
                dot.classList.toggle('status-online', isOnline);
                dot.classList.toggle('status-offline', !isOnline);
            }
        });
    }

    // Removed handleRealTimeUpdate and stats updater; using named SSE events


    handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            this.sendMessage();
        }
    }

    handleInputChange() {
        const length = this.messageInput.value.length;
        const charCount = this.charCountText || document.querySelector('.char-count');
        if (charCount) charCount.textContent = `${length}/500`;

        // Enable/disable send button
        this.sendButton.disabled = length === 0;

        // Change color based on character count
        if (charCount) {
            if (length > 450) {
                charCount.style.color = '#c7232c';
            } else if (length > 400) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#d6b25e'; // Match gold from screenshot
            }
        }
    }

    handleTyping() {
        if (!this.currentCustomer) return;

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
        if (!this.currentCustomer) return;

        fetch(this.apiBase, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_typing',
                isTyping,
                customerID: this.currentCustomer.userID || this.currentCustomer.customerID
            })
        }).catch(() => { });
    }

    showTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            const nameEl = typingIndicator.querySelector('.typing-name');
            if (nameEl && this.currentCustomer) {
                nameEl.textContent = `${this.currentCustomer.name} is typing...`;
            }
            typingIndicator.classList.add('active');
        }
    }

    hideTypingIndicator() {
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.classList.remove('active');
        }
    }

    handleIncomingNotification(msg) {
        // 1. Find customer in list
        const customerItem = document.querySelector(`[data-customer-id="${msg.userID}"]`);

        if (customerItem) {
            // Check online status via DOM indicators (more reliable than async arrays)
            const statusDot = customerItem.querySelector('.status-dot');
            const isOnline = statusDot && statusDot.classList.contains('status-online');

            if (!isOnline) {
                console.log('Suppressing notification for offline user:', msg.userID);
                return; // Do nothing if they are offline
            }

            // 2. Play Sound
            this.playNotificationSound();

            // 3. Update badge
            const metaDiv = customerItem.querySelector('.customer-meta');
            let badge = metaDiv ? metaDiv.querySelector('.unread-badge') : null;

            if (metaDiv && !badge) {
                badge = document.createElement('span');
                badge.className = 'unread-badge';
                badge.textContent = '0';
                metaDiv.appendChild(badge);
            }

            if (badge) {
                let count = parseInt(badge.textContent) || 0;
                badge.textContent = count + 1;

                // Hide status dot when notification is present
                const dot = metaDiv.querySelector('.status-dot');
                if (dot) dot.style.display = 'none';
            }

            // Visual cues
            customerItem.style.backgroundColor = 'rgba(214, 178, 94, 0.1)'; // Subtle gold highlight
            setTimeout(() => {
                if (!customerItem.classList.contains('active')) {
                    customerItem.style.backgroundColor = '';
                } else {
                    customerItem.style.backgroundColor = '#e9ecef'; // Revert to active color
                }
            }, 1000);

            // Update last message preview
            const lastMsgDiv = customerItem.querySelector('.customer-last-message');
            if (lastMsgDiv) {
                let lastMessagePreview = (msg.message || '').trim();
                if (lastMessagePreview.startsWith('> "')) {
                    const parts = lastMessagePreview.split(/[\r\n]+/);
                    if (parts.length > 0) {
                        lastMessagePreview = parts[parts.length - 1].trim();
                    }
                }
                lastMsgDiv.textContent = lastMessagePreview;
                lastMsgDiv.style.fontWeight = 'bold';
            }
        } else {
            // Customer not in list - refresh to show them
            this.loadCustomers();
        }
    }

    playNotificationSound() {
        // Simple distinct beep
        try {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gainNode = context.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(context.destination);

            oscillator.type = 'sine';
            oscillator.frequency.value = 800;
            gainNode.gain.value = 0.1;

            oscillator.start();
            setTimeout(() => {
                oscillator.stop();
            }, 150);
        } catch (e) {
            console.warn('Audio play failed', e);
        }
    }
    getAvatar(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    }

    formatMessage(text) {
        // Basic formatting for links and line breaks
        return text
            .replace(/\n/g, '<br>')
            .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color: inherit; text-decoration: underline;">$1</a>');
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
        // Trigger input event to update char count/height
        this.handleInputChange();
    }

    closeReplyPreview() {
        this.currentReply = null;
        const preview = document.getElementById('replyPreview');
        if (preview) {
            preview.classList.remove('active');
        }
    }

    scrollToBottom() {
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    }

    async clearAllMessages() {
        if (this.clearAllMessagesModal) {
            this.clearAllMessagesModal.show();
        } else {
            // Fallback
            if (confirm('Are you sure you want to clear ALL messages from ALL customers? This cannot be undone.')) {
                this.executeClearAllMessages();
            }
        }
    }

    async executeClearAllMessages() {
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clear_all_messages' })
            });
            const data = await response.json();
            if (data.success) {
                this.chatMessages.innerHTML = '';
                this.loadCustomers();
                if (this.clearAllMessagesModal) this.clearAllMessagesModal.hide();
                location.reload(); // Hard reload to clear active states and dashboard
            } else {
                this.showNotification('Error clearing message index: ' + data.error, 'Database Exception');
            }
        } catch (error) {
            console.error('Error clearing messages:', error);
            this.showNotification('The request to clear data failed. Please verify your admin permissions.', 'Action Denied');
        }
    }

    showNotification(message, title = 'Notice') {
        const textEl = document.getElementById('notificationMessage');
        const titleEl = document.getElementById('notificationTitle');
        if (textEl) textEl.textContent = message;
        if (titleEl) titleEl.textContent = title;
        if (this.notificationModal) this.notificationModal.show();
    }
}

// Initialize admin chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminChat();
});
