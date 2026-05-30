<?php
session_start();

session_unset();
$_SESSION = array();
$_SESSION['cart'] = [];
$_SESSION['cart_count'] = 0;
$_SESSION['wishlist'] = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();
session_write_close();

?>
<!DOCTYPE html>
<html>

<body>
    <script>
        // Clear badge triggers
        localStorage.removeItem('jde_cart_updated');
        localStorage.removeItem('jde_wishlist_updated');
        // Do not clear jde_last_msg_id to prevent old notifications from popping up again on login
        window.location.href = 'login.php' + (window.location.search || '');
    </script>
</body>

</html>
<?php
exit;
?>