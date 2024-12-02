<?php

namespace SPSOstrov\SSO;

use Exception;

/**
 * This class represents the SPŠ Ostrov single sign-on procedure.
 */
class SSO
{
    const SSO_GATEWAY_URL = "https://titan.spsostrov.cz/ssogw/";
    const SSO_GATEWAY_CHECK_URL = "service-check.php";
    const SERVICE_ARG = "service";
    const TOKEN_ARG = "ticket";

    private static bool $aliasesEnabled = false;

    public static function enableAliases(): void
    {
        if (!self::$aliasesEnabled) {
            class_alias(SSO::class, "SSO");
            class_alias(SSOUser::class, "SSOUser");
            self::$aliasesEnabled = true;
        }
    }

    /** @var string SSO gateway URL */
    private string $ssoGatewayUrl;

    /** @var string SSO gateway check URL */
    private string $ssoGatewayCheckUrl;

    /** @var string Class to create an SSO User */
    private string $ssoUserClass;

    /**
     * Create a new instnace of the SSO library.
     *
     * @param string|null $ssoGatewayUrl SSO gateway URL (use default if not speicfied)
     * @param string|null $ssoGatewayCheckUrl SSO gateway check URL (use default if not specified)
     */
    public function __construct(?string $ssoGatewayUrl = null, ?string $ssoGatewayCheckUrl = null, ?string $ssoUserClass = null)
    {
        $this->ssoGatewayUrl = $ssoGatewayUrl ?? self::SSO_GATEWAY_URL;
        $this->ssoGatewayCheckUrl = $this->resolveRelativePath($ssoGatewayCheckUrl ?? self::SSO_GATEWAY_CHECK_URL, $this->ssoGatewayUrl);
        $this->ssoUserClass = $ssoUserClass ?? SSOUser::class;
        if (!is_a($this->ssoUserClass, SSOUser::class, true)) {
            throw new Exception("ssoUserClass needs to be a subclass of " . SSOUser::class);
        }
    }

    /**
     * Do the whole "magic" in a single step.
     *
     * @return SSOUser|null instance of SSOUser representing the currently logged-in user or null
     */
    public function doLogin(): ?SSOUser
    {
        if ($this->getDefaultToken() !== null) {
            return $this->getLoginCredentials();
        } else {
            $this->doRedirect();
        }
    }

    /**
     * Do the whole "magic" in a single step. The same as doLogin(), but the user is returned as an associative array.
     *
     * @return array|null array representing the currently logged-in user or null
     */
    public function doLoginAsArray(): ?array
    {
        $user = $this->doLogin();
        if ($user !== null) {
            return $user->asArray();
        }
        return $user;
    }

    /**
     * Do the second phase of the login procedure
     *
     * @param string|null $token The token returned from SSO
     * @param string|null $backUrl The url the user was redirected to from SSO 
     *                             (May be specified as full url, absolute path or relative path)
     * @return SSOUser|null instance of SSOUser representing the currently logged-in user or null
     */
    public function getLoginCredentials(?string $token = null, ?string $backUrl = null): ?SSOUser
    {
        $token = $token ?? $this->getDefaultToken();
        if ($token === null) {
            return null;
        }
        return $this->querySSOCheckUrl($token, $backUrl);
    }

    /**
     * Return the token passed by SSO to phase 2.
     * 
     * @return string|null SSO token or null if SSO token is not available
     */
    public function getDefaultToken(): ?string
    {
        return $_GET[self::TOKEN_ARG] ?? null;
    }

    /**
     * Do the first phase of the login procedure (redirect to SSO)
     * 
     * @param string|null $backUrl The url the user was redirected to from SSO 
     *                             (May be specified as full url, absolute path or relative path)
     * @return string|null SSO token or null if SSO token is not available
     */
    public function doRedirect(?string $backUrl = null): void
    {
        $redirectUrl = $this->getRedirectUrl($backUrl);
        header(sprintf("Location: %s", $redirectUrl));
        exit;
    }

    /**
     * Return the url where to redirect as the first phase of the login. (in case the APP want to
     * make the redirection by its own.
     * @param string|null $backUrl The url the user was redirected to from SSO 
     *                             (May be specified as full url, absolute path or relative path)
     * @return string SSO URL where to redirect
     */
    public function getRedirectUrl(?string $backUrl = null): string
    {
        $realBackUrlEncoded = base64_encode($x = $this->getRealBackUrl($backUrl));
        $delim = (strpos("?", $this->ssoGatewayUrl) === false) ? '?' : '&';
        return sprintf(
            "%s%s%s=%s",
            $this->ssoGatewayUrl,
            $delim,
            urlencode(self::SERVICE_ARG),
            urlencode($realBackUrlEncoded)
        );
    }

    private function querySSOCheckUrl(string $token, ?string $backUrl): ?SSOUser
    {
        $realBackUrlEncoded = base64_encode($x = $this->getRealBackUrl($backUrl));
        $delim = (strpos("?", $this->ssoGatewayCheckUrl) === false) ? '?' : '&';
        $checkUrl = sprintf(
            "%s%s%s=%s&%s=%s",
            $this->ssoGatewayCheckUrl,
            $delim,
            urlencode(self::SERVICE_ARG),
            urlencode($realBackUrlEncoded),
            urlencode(self::TOKEN_ARG),
            urlencode($token)
        );
        $result = @file_get_contents($checkUrl);
        if (!is_string($result)) {
            return null;
        }
        $result = $this->parseSSOResponseToArray($result);
        if (!isset($result["login"]) || !isset($result["name"])) {
            return null;
        }
        return new $this->ssoUserClass($result);
    }

    private function parseSSOResponseToArray(string $userDataString): array
    {
        $data = [];
        foreach (explode("\n", $userDataString) as $record) {
            $record = trim($record);
            if ($record === "") {
                continue;
            }
            $record = explode(":", $record, 2);
            if (count($record) !== 2) {
                continue;
            }
            if (!preg_match('/^[a-z_]+$/', $record[0])) {
                continue;
            }
            list($key, $value) = $record;
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            $data[$key][] = $value;
        }
        return $data;
    }


    private function getRealBackUrl(?string $backUrl): string
    {
        return $this->resolveRelativePath($backUrl, $this->detectMyUrl());
    }

    private function detectMyUrl(): string
    {
        return $this->detectMyUrlHost() . $this->detectMyUrlPath();
    }

    private function detectMyUrlHost(): string
    {
        return $this->detectMyUrlScheme() . $_SERVER['HTTP_HOST'];
    }

    private function detectMyUrlScheme(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) ? 'https' : 'http';
        return sprintf("%s://", $scheme);
    }

    private function getDirFromPath(string $path): string
    {
        if (substr($path, "-1") !== "/") {
            $path = dirname($path) . "/";
        }
        return $path;
    }

    private function detectMyUrlPath(): string
    {
        return strtok($_SERVER["REQUEST_URI"], '?');
    }

    private function resolveRelativePath(?string $relativePath, string $absoluteUrl): string
    {
            if ($relativePath === null || $relativePath === '') {
                return $absoluteUrl;
            } if (preg_match('|^https?://|', $relativePath)) {
                return $relativePath;
            } else {
                $parsed = parse_url($absoluteUrl);
                if ($parsed === false || !isset($parsed['path']) || !isset($parsed['host']) || !isset($parsed['scheme'])) {
                    throw new Exception("Invalid absolute URL");
                }
                $path = $parsed['path'];
                if (substr($relativePath, 0, 1) === '/') {
                    $path = $relativePath;
                } else {
                    $path = $this->canonizePath($this->getDirFromPath($path) . $relativePath);
                }
                $port = isset($parsed['port']) ? sprintf(":%d", $parsed['port']) : '';
                return sprintf("%s://%s%s%s", $parsed['scheme'], $parsed['host'], $port, $path);
            }
    }

    private function canonizePath(string $path): string
    {
        $finalPath = [];
        foreach(explode("/", $path) as $name) {
            if ($name === '.' || $name === '') {
                continue;
            }
            if ($name === '..') {
                array_pop($finalPath);
            } else {
                $finalPath[] = $name;
            }
        }
        return '/' . implode('/', $finalPath);
    }
}
