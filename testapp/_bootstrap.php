<?php

require_once dirname(__DIR__) . "/lib/libsso.php";

define("APP_VERSION", 1);

function logIn(?SSOUser $user)
{
    $_SESSION["user"] = $user;
    $_SESSION["app_version"] = APP_VERSION;
}

function getLoggedInUser(): ?SSOUser
{
    $sessionAppVersion = $_SESSION["app_version"] ?? 0;
    if ($sessionAppVersion !== APP_VERSION) {
        logIn(null);
    }
    return $_SESSION["user"] ?? null;
}
