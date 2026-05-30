<?php
/**
 * chat.php — Backend controller
 * Handles session start, role check, then renders the view.
 */
session_start();
?>
<script>
    window.isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>
<?php
// Strict Role Check: Employees redirect to admin_chat.php
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee') {
    header("Location: admin_chat.php");
    exit();
}

require '../html/chat.view.php';