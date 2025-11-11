<?php
define('AUTH_ALLOW_GOOGLE_PUBLIC', true);
require_once 'auth.php';

google_logout();
pin_clear_validation();

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'login.php';
header('Location: ' . $redirect);
exit;
