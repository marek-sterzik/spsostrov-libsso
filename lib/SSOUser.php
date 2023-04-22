<?php

/**
 * This class represents the currently logged in user from SSO.
 */
class SSOUser
{
    /** @var string user's login */
    private string $login;
    
    /** @var string user's full name */
    private string $name;
    
    /** @var string[] user's groups */
    private array $groups = [];

    /** @var string|null user's e-mail */
    private ?string $email = null;

    /** @var string|null group name */
    private ?string $groupName = null;

    /** @var string|null auth by info */
    private ?string $authBy = null;

    /** @var string|null ou simple info */
    private ?string $ouSimple = null;

    /** @var array other data not yet understood by the library */
    private array $otherData;

    /**
     * Create an user. The user should never be created manually. The user is
     * always created by the class SSO.
     */
    public function __construct(array $data)
    {
        $this->login = $this->extractKey($data, "login");
        $this->name = $this->extractKey($data, "name");
        $this->groups = $this->extractKey($data, "group", true);
        $this->email = $this->extractKey($data, "mail");
        $this->groupName = $this->extractKey($data, "group_name");
        $this->groupName = ($this->groupName === '') ? null : $this->groupName;
        $this->authBy = $this->extractKey($data, "auth_by");
        $this->ouSimple = $this->extractKey($data, "ou_simple");
        $this->otherData = $data;

    }

    /**
     * @return string User's login
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @return string User's full name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null User's group name
     */
    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    /**
     * @return array User's groups where the user belongs to
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Test if the user belongs to a particular group.
     * @param string $group The group being tested
     * @return bool true if the user belongs to the particular group
     */
    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->groups);
    }

    /**
     * @return string|null User's e-mail
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null User's auth by info
     */
    public function getAuthBy(): ?string
    {
        return $this->authBy;
    }

    /**
     * @return string|null User's ou simple info
     */
    public function getOUSimple(): ?string
    {
        return $this->ouSimple;
    }

    /**
     * @return array User's data not yet understood by the library
     */
    public function getOtherData(): array
    {
        return $this->otherData;
    }

    /**
     * Print the user as HTML.
     * @param bool $return true if the html string should be returned
     *                     false if the html string should be echoed to stdout
     * @return string the returned html in case $return is true, null otherwise
     */
    public function prettyPrint(bool $return = false): ?string
    {
        return (new SSOUserPrinter())->print($this, $return);
    }

    /**
     * Convert the user to a representation by an associative array.
     * @return array The associative array representation of the user
     */
    public function asArray(): array
    {
        return [
            "login" => $this->login,
            "name" => $this->name,
            "groups" => $this->groups,
            "email" => $this->email,
            "otherData" => $this->otherData,
        ];
    }

    private function extractKey(array &$data, string $key, bool $multiValue = false)
    {
        $extracted = $data[$key] ?? [];
        unset($data[$key]);
        return $multiValue ? $extracted : array_pop($extracted);
    }
}
