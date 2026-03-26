<?php

require_once __DIR__ . "/_bootstrap.php";

session_start();


if ($_GET['logout'] ?? null) {
    logIn(null);
} elseif (($_GET['dummy'] ?? null) !== null) {
    logIn(SSOUser::createDummy($_GET['dummy']));
} else {
    $sso = new SSO($ssoInstance ?? null);
    logIn($sso->doLogin());
}

header("Location: index.php");

