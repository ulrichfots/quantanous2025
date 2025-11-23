<?php
require_once 'auth.php';
$pinRedirect = $pinRedirect ?? ($_SERVER['REQUEST_URI'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D32">
    <title>Code PIN requis - quantanous</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="pin-lock-body" data-pin-redirect="<?php echo htmlspecialchars($pinRedirect, ENT_QUOTES, 'UTF-8'); ?>">
    <?php include_once 'pin-modal.php'; ?>
    <script src="assets/js/pin-lock.js"></script>
</body>
</html>
