<?php

require_once __DIR__ . "/classes/SSO.php";
require_once __DIR__ . "/classes/SSOUser.php";
require_once __DIR__ . "/classes/SSOUserPrinter.php";

class_alias(SPSOstrov\SSO\SSO::class, "SSO");
class_alias(SPSOstrov\SSO\SSOUser::class, "SSOUser");
class_alias(SPSOstrov\SSO\SSOUserPrinter::class, "SSOUserPrinter");
