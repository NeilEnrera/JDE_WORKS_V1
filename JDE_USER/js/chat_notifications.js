/**
 * Chat Notification System (Bubble Design)
 * Handles real-time admin message alerts via SSE.
 */

class JDEChatNotifications {
    constructor() {
        this.popup = null;
        this.unreadCount = 0;
        this.apiBase = 'chat_api.php';
        this.lastMessageId = localStorage.getItem('jde_last_msg_id') || 0;
        this.dismissTimer = null;

        // Exclude pages where the popup shouldn't appear
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        if (currentPage === 'profile.php' || currentPage === 'chat.php' || currentPage.startsWith('admin')) {
            console.log('Chat notifications disabled on this page');
            return;
        }

        this.init();
    }

    init() {
        this.createElements();
        this.fetchInitialUnread();
        this.startRealTimeListener();
    }

    createElements() {
        const html = `
            <div id="chatNotificationContainer" class="chat-notif-container">
                <!-- The Notification Bubble -->
                <div id="chatNotificationPopup" class="chat-notif-bubble">
                    <div class="bubble-content" id="chatNotificationBody">
                        <button class="bubble-close" id="closeBubbleBtn" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                        <span class="bubble-sender" id="notifSender">Admin</span>
                        <span class="bubble-text" id="notifText">Message...</span>
                    </div>
                </div>
                
                <!-- The Persistent Chat FAB -->
                <div class="chat-fab-wrapper">
                    <a href="chat.php" class="chat-fab" id="chatFab" title="Live Chat">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="fab-icon">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span class="fab-badge" id="fabBadge" style="display: none;">0</span>
                    </a>
                    <button class="fab-dismiss-btn" id="dismissChatFab" title="Hide Chat">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        this.popup = document.getElementById('chatNotificationPopup');

        // Check if previously dismissed and no new messages
        const containerEl = document.getElementById('chatNotificationContainer');
        const dismissedId = parseInt(localStorage.getItem('jde_chat_dismissed_id') || 0);
        if (dismissedId >= this.lastMessageId && this.lastMessageId > 0) {
            containerEl.style.display = 'none';
        }

        // Dismiss FAB completely
        document.getElementById('dismissChatFab').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('chatNotificationContainer').style.display = 'none';
            localStorage.setItem('jde_chat_dismissed_id', this.lastMessageId);
        });

        // Close bubble ONLY (not FAB)
        document.getElementById('closeBubbleBtn').addEventListener('click', (e) => {
            e.stopPropagation();
            this.hidePopup();
            // Mark the CURRENT last message as dismissed so it doesn't pop up again
            localStorage.setItem('jde_chat_dismissed_id', this.lastMessageId);
        });

        // Click Bubble to open chat
        document.getElementById('chatNotificationBody').addEventListener('click', (e) => {
            if (e.target.closest('#closeBubbleBtn')) return;
            window.location.href = 'chat.php';
        });
    }

    fetchInitialUnread() {
        fetch(`${this.apiBase}?action=get_unread_count`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.unreadCount > 0) {
                    this.unreadCount = data.unreadCount;
                    this.updateBadge(this.unreadCount);
                    // Do not auto-show popup on page load, wait for new messages
                }
            })
            .catch(err => console.error('Error fetching unread count:', err));
    }

    updateBadge(count) {
        const badge = document.getElementById('fabBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    startRealTimeListener() {
        this.eventSource = new EventSource(`chat_stream.php?lastMessageId=${this.lastMessageId}`);

        this.eventSource.addEventListener('new_message', (event) => {
            try {
                const msg = JSON.parse(event.data);
                this.lastMessageId = msg.id;
                localStorage.setItem('jde_last_msg_id', msg.id);

                // Only react to admin messages if we are a customer and it is unread
                if (msg.isAdmin === true && msg.isRead === 0) {
                    this.unreadCount++;
                    this.updateBadge(this.unreadCount);

                    this.showPopup({
                        id: msg.id,
                        sender: msg.sender,
                        message: msg.message
                    });

                    this.playNotifSound();
                }
            } catch (error) {
                console.error('Error parsing SSE message:', error);
            }
        });

        this.eventSource.addEventListener('user_status', (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data.status === 'suspended') {
                    const messageEl = document.getElementById('suspendedMessage');
                    if (messageEl && data.message) {
                        messageEl.textContent = data.message;
                    }
                    const modalEl = document.getElementById('suspendedModal');
                    if (modalEl) {
                        const suspendedModal = new bootstrap.Modal(modalEl, { backdrop: false });
                        suspendedModal.show();
                    }
                    
                    // Force redirect if they don't click anything after 10 seconds
                    setTimeout(() => {
                        window.location.href = 'logout.php?status=suspended';
                    }, 10000);
                }
            } catch (error) {
                console.error('Error parsing user_status event:', error);
            }
        });

        this.eventSource.onerror = (e) => {
            this.eventSource.close();
            setTimeout(() => this.startRealTimeListener(), 5000);
        };
    }

    showPopup(data) {
        // Prevent showing the popup if this specific message (or any older) was already dismissed
        const dismissedId = parseInt(localStorage.getItem('jde_chat_dismissed_id') || 0);
        if (data.id && data.id <= dismissedId) {
            console.log('Suppressing already dismissed notification:', data.id);
            return;
        }

        const containerEl = document.getElementById('chatNotificationContainer');
        const senderEl = document.getElementById('notifSender');
        const textEl = document.getElementById('notifText');

        if (!containerEl) return;

        // Restore visibility if it was previously dismissed
        containerEl.style.display = '';

        // Check for reply pattern: > "Quote" - Sender\n\nMessage
        let messageText = (data.message || '').trim();
        if (messageText.startsWith('> "')) {
            const parts = messageText.split(/[\r\n]+/);
            if (parts.length > 0) {
                messageText = parts[parts.length - 1].trim();
            }
        }

        if (senderEl) senderEl.textContent = data.sender;
        if (textEl) textEl.textContent = messageText;

        this.popup.classList.add('show');

        // Auto-hide after 3 seconds exactly as requested
        if (this.dismissTimer) clearTimeout(this.dismissTimer);
        this.dismissTimer = setTimeout(() => {
            this.hidePopup();
        }, 3000);
    }

    hidePopup() {
        if (this.popup) {
            this.popup.classList.remove('show');
        }
    }

    playNotifSound() {
        const audio = new Audio('assets/sounds/notification.mp3');
        audio.play().catch(() => {
            // Silently fail if browser blocks autoplay
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.isLoggedIn) {
        window.jdeChatNotifs = new JDEChatNotifications();
    }
});
