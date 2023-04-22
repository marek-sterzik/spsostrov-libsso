<?php

require_once dirname(__DIR__)."/lib/libsso.php";

session_start();
$user = $_SESSION["user"] ?? null;

?><!doctype html>
<html>
<head>
    <link rel="stylesheet" href="media/style.css">
</head>
<body>
    <h1>SPŠ Ostrov SSO library test application</h1>
    <div class="userinfo">
        <?php if($user): ?>
            <div>User currently logged in:</div>
            <?php $user->prettyPrint(); ?>
        <?php else: ?>
            <div>No user currently logged in.</div>
            <p class="error">No user info available.</p>
        <?php endif ?>
        <div class="links">
            <a href="sso.php">SSO Login</a>
            <a href="sso.php?logout=1">Logout</a>
        </div>
    </div>
</body>
</html>
