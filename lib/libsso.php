<?php

require_once __DIR__ . "/classes/SSO.php";
require_once __DIR__ . "/classes/SSOUser.php";
require_once __DIR__ . "/classes/SSOUserPrinter.php";

if (!defined("SPSOSTROV_SSO_NO_ALIASES")) {
    SPSOstrov\SSO\SSO::enableAliases();
}
