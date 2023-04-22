<?php

// Load library either from vendor/autoload.php if available (composer variant)
// or load libsso from lib/libsso.php (default configuration)
foreach(["/vendor/autoload.php", "/lib/libsso.php", "/../lib/libsso.php"] as $file) {
    $file = __DIR__ . $file;
    if (is_file($file)) {
        require_once $file;
        break;
    }
}

// we want class aliases enabled in this application
// (not necessary if library is loaded through lib/libsso.php)
SPSOstrov\SSO\SSO::enableAliases();

define("APP_VERSION", 1);

// Set the SSO User into session
function logIn(?SSOUser $user)
{
    $_SESSION["user"] = $user;
    $_SESSION["app_version"] = APP_VERSION;
}

// Get the SSO User from session. The function is protected against changes in the session data structure.
function getLoggedInUser(): ?SSOUser
{
    $sessionAppVersion = $_SESSION["app_version"] ?? 0;
    if ($sessionAppVersion !== APP_VERSION) {
        logIn(null);
    }
    $user = $_SESSION["user"] ?? null;
    return ($user instanceof SSOUser) ? $user : null;
}
