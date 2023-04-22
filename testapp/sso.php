<?php

require_once __DIR__ . "/_bootstrap.php";

session_start();


if ($_GET['logout'] ?? null) {
    logIn(null);
} else {
    $sso = new SSO();
    logIn($sso->doLogin());
}

header("Location: index.php");

