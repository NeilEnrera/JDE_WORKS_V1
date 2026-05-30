<?php
$bodyClass = 'admin-chat-page';
include __DIR__ . '/../../JDE_ADMIN/html/fragments/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="../css/admin_chat.css">

<!-- Top Header -->
<header class="chat-top-bar">
    <div class="header-left">
        <div class="header-logo">
            <i class="bi bi-chat-dots-fill" style="font-size: 24px; color: #d6b25e; margin-right: 12px;"></i>
            <div class="logo-text">
                <h1 style="font-size: 18px; margin: 0; font-weight: 700;">JDE Messages</h1>
                <p style="font-size: 12px; margin: 0; opacity: 0.8;">Signed in as <span id="adminName" style="font-weight: 600; color: #d6b25e;">Admin</span></p>
            </div>
        </div>
    </div>
    <div class="header-right">
        <!-- Dashboard info placeholder -->
    </div>
</header>

<div class="chat-main-wrapper">
    <!-- Sidebar (Chat Customers) -->
    <aside class="chat-sidebar">
        <div class="sidebar-search">
            <div class="search-inner">
                <i class="bi bi-search"></i>
                <input type="text" id="customerSearch" placeholder="Search customers...">
            </div>
        </div>

        <div class="chat-sidebar-inner">
            <div class="quick-reply-label"><i class="bi bi-lightning-charge-fill"></i> Quick replies</div>
            <div class="quick-response-pills">
                <button class="pill quick-response" data-response="Hello! How can I help you today?">Hello</button>
                <button class="pill quick-response" data-response="Thank you for contacting JDE Works!">Thanks</button>
                <button class="pill quick-response" data-response="Please provide your order number.">Order
                    Help</button>
                <button class="pill quick-response" data-response="Our shop is open Mon-Sat, 9AM to 6PM.">Hours</button>
                <button class="pill quick-response"
                    data-response="Please wait a moment while I check that for you.">Wait</button>
                <button class="pill quick-response"
                    data-response="Your order is currently being processed.">Status</button>
                <button class="pill quick-response"
                    data-response="You can contact us via phone at 0912-345-6789.">Contact</button>
                <button class="pill quick-response"
                    data-response="For technical support, please email support@jdeworks.com.">Support</button>
            </div>

            <div class="sidebar-section-title"><i class="bi bi-people-fill"></i> Customers</div>
            <div class="customer-list" id="customerList"></div>
        </div>
    </aside>

    <!-- Chat Area -->
    <main class="chat-content">
        <div class="chat-content-header" id="chatContentHeader">
            <div class="header-info">
                <h2 id="currentCustomerName" style="font-size: 17px; font-weight: 700; color: #0b2e46; margin: 0;">
                    Select a conversation</h2>
                <p id="currentCustomerDesc" style="font-size: 13px; color: #94a3b8; margin: 2px 0 0;">Choose a customer
                    to start chatting</p>
            </div>
            <div class="header-actions">
                <button class="btn-action-light" id="markAsRead" title="Mark session as read">
                    <i class="bi bi-check2-all"></i>
                </button>
                <button class="btn-action-light" id="customerInfo" title="View customer info">
                    <i class="bi bi-info-circle"></i>
                </button>
            </div>
        </div>

        <div class="chat-messages-area" id="chatMessages">
            <div class="welcome-dashboard">
                <div class="welcome-icon"><i class="bi bi-headset"></i></div>
                <h2>Admin Chat Dashboard</h2>
                <p>Real-time customer support messaging system. Click on a user on the left to begin.</p>
            </div>
        </div>

        <div class="typing-indicator" id="typingIndicator">
            <span class="typing-name"></span>
            <div class="typing-dots"><span></span><span></span><span></span></div>
        </div>

        <div class="chat-input-wrapper">
            <div class="input-container-row">
                <div class="input-container">
                    <input type="text" id="messageInput" placeholder="Type a message..." maxlength="500">
                    <button class="btn-send-gold" id="sendMessage"><i class="bi bi-send-fill"></i></button>
                </div>
            </div>
            <div class="input-footer">
                <span class="char-count" id="charCountText">0/500</span>
                <span class="instructions">Enter to send · Shift+Enter for new line</span>
            </div>
        </div>
    </main>
</div>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin-chat-controller.js"></script>
<script src="../../JDE_USER/js/admin_chat.js"></script>

<!-- Customer Information Modal -->
<div class="modal fade" id="customerInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px;">
            <div class="modal-body" id="customerInfoBody"></div>
        </div>
    </div>
</div>

<!-- Clear All Messages Confirmation Modal -->
<div class="modal fade" id="clearAllMessagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="background: #1a1a1a; border-radius: 24px; border: 1px solid rgba(214, 178, 94, 0.2); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            <div class="modal-body p-4 text-center">
                <div class="mb-3" style="width: 60px; height: 60px; background: rgba(231, 76, 60, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="bi bi-trash3-fill" style="font-size: 28px; color: #e74c3c;"></i>
                </div>
                <h5 class="modal-title mb-2" style="color: #ffffff; font-weight: 700; font-family: 'Outfit', sans-serif;">Clear All History?</h5>
                <p style="color: #94a3b8; font-size: 15px; line-height: 1.5; margin-bottom: 24px; font-family: 'Outfit', sans-serif;">
                    This will permanently delete ALL messages from ALL customers. This action cannot be undone.
                </p>
                <div class="d-flex gap-3">
                    <button type="button" class="btn flex-grow-1" data-bs-dismiss="modal" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; font-weight: 600; border-radius: 14px; padding: 12px; border: none; font-family: 'Outfit', sans-serif;">
                        Cancel
                    </button>
                    <button type="button" id="confirmClearAll" class="btn flex-grow-1" style="background: #e74c3c; color: #ffffff; font-weight: 700; border-radius: 14px; padding: 12px; border: none; font-family: 'Outfit', sans-serif;">
                        Delete All
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal (Replaces Browser Alerts) -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content"
            style="background: #1a1a1a; border-radius: 24px; border: 1px solid rgba(214, 178, 94, 0.2); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            <div class="modal-body p-4 text-center">
                <div class="mb-3"
                    style="width: 60px; height: 60px; background: rgba(214, 178, 94, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="bi bi-exclamation-circle" style="font-size: 28px; color: #d6b25e;"></i>
                </div>
                <h5 class="modal-title mb-2" id="notificationTitle"
                    style="color: #ffffff; font-weight: 700; font-family: 'Outfit', sans-serif;">Notice</h5>
                <p id="notificationMessage"
                    style="color: #94a3b8; font-size: 15px; line-height: 1.5; margin-bottom: 24px; font-family: 'Outfit', sans-serif;">
                    Please select a customer first</p>
                <button type="button" class="btn w-100" data-bs-dismiss="modal"
                    style="background: #d6b25e; color: #1a1a1a; font-weight: 700; border-radius: 14px; padding: 12px; transition: all 0.3s ease; border: none; font-family: 'Outfit', sans-serif;">
                    Got it
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../JDE_ADMIN/html/fragments/footer.php'; ?>