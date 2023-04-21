<?php

require_once dirname(__DIR__)."/lib/libsso.php";

session_start();


if ($_GET['logout'] ?? null) {
    $_SESSION["user"] = null;
} else {
    $sso = new SSO();
    $_SESSION["user"] = $sso->doLogin();
}

header("Location: index.php");

