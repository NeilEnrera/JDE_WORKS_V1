<?php
/**
 * admin_chat.php — Backend controller (JDE_ADMIN)
 * Handles session and role guard, then renders the admin chat view.
 */
session_start();

// Strict Role Check: Employees with levels 1 or 2 only
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Employee' || !in_array($_SESSION['access_level'], [1, 2])) {
    header('Location: ../../JDE_USER/backend/login.php');
    exit();
}

// Required so sidebar.php has $conn for the unread messages badge query
require_once '../../JDE_USER/backend/db_connection.php';

// Mark contact inquiries as read when visiting the messages page
$tableCheck = $conn->query("SHOW TABLES LIKE 'tbl_contactMessages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $conn->query("UPDATE tbl_contactMessages SET isRead = 1 WHERE isRead = 0");
}

// Set page title before the view is loaded (matches convention of all other admin controllers)
$pageTitle = 'Admin Chat | Customer Support';
$bodyClass = 'admin-chat-page';
?>
<script>
    window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>
<?php
require '../html/admin_chat.view.php';
