<?php

require_once dirname(__DIR__)."/lib/libsso.php";

session_start();
$user = $_SESSION["user"] ?? null;

?><!doctype html>
<html>
<body>
    <h1>SPŠ Ostrov SSO library test application</h1>
    <div>
        User currently logged in:
        <?php echo $user ? $user->prettyPrint(true) : "None"; ?>
    </div>
    <div>
        <a href="sso.php">SSO Login</a>,
        <a href="sso.php?logout=1">Logout</a>,
    </div>
</body>
</html>
