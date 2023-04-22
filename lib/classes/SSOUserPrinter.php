<?php

namespace SPSOstrov\SSO;

/**
 * This class is responsible for pretty-printing the content of the SSOUser object.
 */
class SSOUserPrinter
{
    static bool $cssPrinted = false;

    public function print(SSOUser $user, bool $return = true, ?bool $printCss = null): ?string
    {
        if ($printCss === null) {
            $printCss = !self::$cssPrinted;
            self::$cssPrinted = true;
        }
        $html = $this->getHtml($user);
        if ($printCss) {
            $html = sprintf("<style>%s</style>%s",$this->getCss(), $html);
        }
        if ($return) {
            return $html;
        }
        echo $html;
        return null;
    }

    private function getCss(): string
    {
        $css = "";
        $css .= "table.sso_user_table {background-color: #404040; border-radius: .4em; color: white; box-shadow: 3px 3px 5px #a0a0a0;}\n";
        $css .= "table.sso_user_table th, table.sso_user_table td {padding: .2em .5em;}\n";
        $css .= "table.sso_user_table th {text-align: right;}\n";
        $css .= "table.sso_user_table th.table_head {color: red; text-align: center}\n";
        $css .= "table.sso_user_table span.true {color: green; font-weight: bold;}\n";
        $css .= "table.sso_user_table span.false {color: red; font-weight: bold;}\n";
        return $css;
    }

    private function getHtml($user): string
    {
        $html = "";
        $html .= "<table class=\"sso_user_table\">\n";
        $html .= "<tr><th colspan=\"2\" class=\"table_head\">SSO user info</th></tr>\n";
        $html .= $this->getRowHtml("Login", $user->getLogin());
        $html .= $this->getRowHtml("Name", $user->getName());
        $html .= $this->getRowHtml("E-mail", $user->getEmail());
        $html .= $this->getRowHtml("Group", $user->getGroupName());
        $html .= $this->getRowHtml("Groups", $user->getGroups());
        $html .= $this->getRowHtml("Auth by", $user->getAuthBy());
        $html .= $this->getRowHtml("OU Simple", $user->getOUSimple());
        $html .= $this->getRowHtml("Is teacher", $user->isTeacher());
        $html .= $this->getRowHtml("Is student", $user->isStudent());
        if ($user->isStudent()) {
            $html .= $this->getRowHtml("Field of study", $user->getFieldOfStudy());
            $html .= $this->getRowHtml("Study entry year", $user->getStudyEntryYear());
        }
        foreach ($user->getOtherData() as $key => $value) {
            $value = empty($value) ? null : implode(", ", $value);
            $html .= $this->getRowHtml(sprintf("Other[%s]", $key), $value);
        }
        $html .= "</table>\n";
        return $html;
    }

    private function getRowHtml(string $heading, $data): string
    {
        $data = $this->convertToString($data);
        return sprintf(
            "<tr><th>%s:</th><td>%s</td></tr>\n",
            htmlspecialchars($heading),
            $data
         );
    }

    private function convertToString($data): string
    {
        if (is_array($data)) {
            $data = array_map(function($item){
                return $this->convertToString($item);
            }, $data);
            if (empty($data)) {
                return "<em>(empty)</em>";
            }
            return implode(", ", $data);
        }
        if ($data === true) {
            return "<span class=\"true\">yes</span>";
        } elseif ($data === false) {
            return "<span class=\"false\">no</span>";
        } elseif ($data === null) {
            return "<em>(empty)</em>";
        } elseif (is_string($data) || is_int($data)) {
            return htmlspecialchars((string)$data);
        } else {
            return "<em>(unknown data type)</em>";
        }
    }
}
