<?php

class SSOUser
{
    private string $login;
    private string $name;
    private array $groups;
    private ?string $email;
    private array $otherData;

    public function __construct(array $data)
    {
        $this->login = $this->extractKey($data, "login");
        $this->name = $this->extractKey($data, "name");
        $this->groups = $this->extractKey($data, "group", true);
        $this->email = $this->extractKey($data, "mail");
        $this->otherData = $data;

    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->groups);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getOtherData(): array
    {
        return $this->otherData;
    }

    public function prettyPrint(bool $return = false): ?string
    {
        return (new SSOUserPrinter())->print($this, $return);
    }

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
