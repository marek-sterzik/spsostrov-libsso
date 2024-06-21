<?php

namespace SPSOstrov\SSO;

/**
 * This class represents the currently logged in user from SSO.
 */
class SSOUser
{
    const OU_TEACHER = "ucitele";
    const OU_STUDENT_REGEXP = "/^(.)([0-9]{2})(.)(.?)$/";
    const OU_STUDENT_REGEXP_FOS_FIELD = 1;
    const OU_STUDENT_REGEXP_YEAR_FIELD = 2;

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

    /** @var string|null ou name info */
    private ?string $ouName = null;

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
        $this->ouName = $this->extractKey($data, "ou_name");
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
     * @return string|null User's ou name info
     */
    public function getOUName(): ?string
    {
        return $this->ouName;
    }

    /**
     * @return bool true if the user is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->ouSimple === self::OU_TEACHER;
    }

    /**
     * @return bool true if the user is a student
     */
    public function isStudent(): bool
    {
        return preg_match(self::OU_STUDENT_REGEXP, $this->ouSimple);
    }

    /**
     * @return string|null Student's class name or null if the user is not a student
     */
    public function getClass(): ?string
    {
        return $this->isStudent() ? $this->getOUName() : null;
    }

    /**
     * @return bool the student's field of study if the user is a student, null otherwise
     */
    public function getFieldOfStudy(): ?string
    {
        $fos = $this->getOUStudentField(self::OU_STUDENT_REGEXP_FOS_FIELD);
        return isset($fos) ? strtoupper($fos) : $fos;
    }

    /**
     * @return bool the student's study entry year
     */
    public function getStudyEntryYear(): ?int
    {
        $year = $this->getOUStudentField(self::OU_STUDENT_REGEXP_YEAR_FIELD);
        if ($year === null) {
            return null;
        }
        $year = (int)$year;
        if ($year < 100) {
            $thisYear = (int)date("Y");
            $yearBase = $thisYear - ($thisYear % 100);
            $year += $yearBase;
            if ($year > $thisYear + 1) {
                $year -= 100;
            }
            return $year;
        }
        if ($year > 2000) {
            return $year;
        }
        return null;
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
            "groupName" => $this->groupName,
            "groups" => $this->groups,
            "email" => $this->email,
            "authBy" => $this->authBy,
            "ouSimple" => $this->ouSimple,
            "ouName" => $this->ouName,
            "isTeacher" => $this->isTeacher(),
            "isStudent" => $this->isStudent(),
            "fieldOfStudy" => $this->getFieldOfStudy(),
            "studyEntryYear" => $this->getStudyEntryYear(),
            "class" => $this->getClass(),
            "otherData" => $this->otherData,
        ];
    }

    private function extractKey(array &$data, string $key, bool $multiValue = false)
    {
        $extracted = $data[$key] ?? [];
        unset($data[$key]);
        return $multiValue ? $extracted : array_pop($extracted);
    }

    private function getOUStudentField(int $field): ?string
    {
        if (!preg_match(self::OU_STUDENT_REGEXP, $this->ouSimple, $matches)) {
            return null;
        }
        return $matches[$field] ?? null;
    }
}
