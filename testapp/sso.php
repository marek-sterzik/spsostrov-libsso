<?php

require_once __DIR__ . "/_bootstrap.php";

session_start();


if ($_GET['logout'] ?? null) {
    $_SESSION["user"] = null;
} else {
    $sso = new SSO();
    $_SESSION["user"] = $sso->doLogin();
}

header("Location: index.php");

