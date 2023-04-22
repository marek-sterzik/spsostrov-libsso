<?php

class SSOUserPrinter
{
    public function print(SSOUser $user, bool $return): ?string
    {
        $html = $this->getHtml($user);
        if ($return) {
            return $html;
        }
        echo $html;
        return null;
    }

    private function getHtml($user): string
    {
        $groups = $user->getGroups();
        $groups = empty($groups) ? null : implode(", ", $groups);
        $html = "";
        $html .= "<table border=\"1\">\n";
        $html .= $this->getRowHtml("Login", $user->getLogin());
        $html .= $this->getRowHtml("Name", $user->getName());
        $html .= $this->getRowHtml("E-mail", $user->getEmail());
        $html .= $this->getRowHtml("Groups", $groups);
        foreach ($user->getOtherData() as $key => $value) {
            $value = empty($value) ? null : implode(", ", $value);
            $html .= $this->getRowHtml(sprintf("Other[%s]", $key), $value);
        }
        $html .= "</table>\n";
        return $html;
    }

    private function getRowHtml(string $heading, ?string $data): string
    {
        if ($data === null) {
            $data = "<em>(empty)</em>";
        } else {
            $data = htmlspecialchars($data);
        }
        return sprintf(
            "<tr><th>%s:</th><td>%s</td></tr>\n",
            htmlspecialchars($heading),
            $data
         );
    }
}
