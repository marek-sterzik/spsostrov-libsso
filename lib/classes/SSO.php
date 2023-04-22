<?php

namespace SPSOstrov\SSO;

/**
 * This class represents the SPŠ Ostrov single sign-on procedure.
 */
class SSO
{
    const SSO_GATEWAY_URL = "https://titan.spsostrov.cz/ssogw/";
    const SSO_GATEWAY_CHECK_URL = "https://titan.spsostrov.cz/ssogw/service-check.php";
    const SERVICE_ARG = "service";
    const TOKEN_ARG = "ticket";

    /** @var string SSO gateway URL */
    private string $ssoGatewayUrl;

    /** @var string SSO gateway check URL */
    private string $ssoGatewayCheckUrl;

    /**
     * Create a new instnace of the SSO library.
     *
     * @param string|null $ssoGatewayUrl SSO gateway URL (use default if not speicfied)
     * @param string|null $ssoGatewayCheckUrl SSO gateway check URL (use default if not specified)
     */
    public function __construct(?string $ssoGatewayUrl = null, ?string $ssoGatewayCheckUrl = null)
    {
        $this->ssoGatewayUrl = $ssoGatewayUrl ?? self::SSO_GATEWAY_URL;
        $this->ssoGatewayCheckUrl = $ssoGatewayCheckUrl ?? self::SSO_GATEWAY_CHECK_URL;
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
        return new SSOUser($result);
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
        if ($backUrl !== null && $backUrl !== '') {
            if (preg_match('|^https?://|', $backUrl)) {
                return $backUrl;
            } elseif ($backUrl[0] === '/') {
                return $this->detectMyUrlHost() . $backUrl;
            } else {
                return $this->canonizePath($this->detectMyUrlDirPath() . $backUrl);
            }
        } else {
            return $this->detectMyUrlHost() . $this->detectMyUrlPath();
        }
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

    private function detectMyUrlDirPath(): string
    {
        $myPath = $this->detectMyUrlPath();
        if (substr($myPath, "-1") !== "/") {
            $myPath = dirname($myPath) . "/";
        }
        return $myPath;
    }

    private function detectMyUrlPath(): string
    {
        return strtok($_SERVER["REQUEST_URI"],'?');
    }
}
