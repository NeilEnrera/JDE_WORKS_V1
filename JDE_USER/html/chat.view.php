<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDE Works Chat - Connect with Our Team</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/chat.css">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php
    $activePage = 'chat';
    include '../backend/navbar.php';
    ?>

    <!-- Chat Container -->
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="chat-header">
                <div class="chat-header-top">
                    <h3><i class="fas fa-comments"></i> JDE Works Chat</h3>
                    <div class="header-online-badge">
                        <span class="header-online-dot"></span>
                        <span>Online</span>
                    </div>
                </div>
                <p>Connect with our tailoring experts</p>
            </div>

            <!-- Sidebar Content -->
            <div class="sidebar-content">
                <!-- Response Time Section -->
                <div class="sidebar-section response-time">
                    <div class="section-card">
                        <i class="fas fa-clock"></i>
                        <div class="section-info">
                            <span>High Responsiveness</span>
                            <small>Typically replies in &lt; 10 mins</small>
                        </div>
                    </div>
                </div>

                <!-- Operating Hours Section -->
                <div class="sidebar-section">
                    <h4><i class="fas fa-calendar-alt"></i> Support Hours</h4>
                    <div class="hours-card">
                        <div class="hours-row">
                            <span>Mon - Fri</span>
                            <span>8:00 AM - 6:00 PM</span>
                        </div>
                        <div class="hours-row">
                            <span>Saturday</span>
                            <span>9:00 AM - 4:00 PM</span>
                        </div>
                        <div class="hours-row status-closed">
                            <span>Sunday</span>
                            <span>Closed</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="sidebar-section contact-info">
                    <h4><i class="fas fa-id-card"></i> Contact Us</h4>
                    <div class="contact-methods">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+63 912 345 6789</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@jdeworks.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-main">
            <div class="chat-messages-header">
                <div class="room-info">
                    <h4>Customer Support</h4>
                    <span class="room-description">Chat with our tailoring experts</span>
                </div>
                <div class="chat-actions">
                    <button class="btn-action" id="clearChat" title="Clear Chat">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn-action" id="toggleNotifications" title="Toggle Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
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
            </div>

            <div class="typing-indicator" id="typingIndicator">
                <span class="typing-name"></span>
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="chat-input-container">
                    <div class="input-group">
                        <input type="text" id="messageInput" placeholder="Type your message here..." maxlength="500">
                        <button class="btn-send" id="sendMessage">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="input-footer">
                        <span class="char-count">0/500</span>
                        <span class="input-hint">Press Enter to send, Shift+Enter for new line</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="chat-login-wall">
                    <i class="fas fa-lock"></i>
                    <p>Please log in to chat with our team</p>
                    <a href="login.php" class="chat-login-btn">Login to Chat</a>
                </div>
            <?php endif; ?>
        </div>
    </div>




    <!-- Clear Chat Confirmation Modal -->
    <div class="modal fade" id="clearChatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-trash-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Clear Chat?</h5>
                    <p class="text-muted mb-4 small">Are you sure you want to clear your chat history? This will only clear the current view.</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light flex-grow-1 border" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                        <button type="button" class="btn btn-danger flex-grow-1" id="confirmClearChat" style="border-radius: 10px;">Clear</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/notifications.js"></script>
    <script src="../js/navbar_cart.js"></script>
    <script src="../js/site.js"></script>
    <script src="../js/chat.js?v=<?php echo time(); ?>"></script>
</body>

</html>