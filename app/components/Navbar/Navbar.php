<?php

namespace App\Components\Navbar;

use App\Entities\UserEntity;
use App\Helpers\LinkHelper;
use App\Modules\TemplateObject;
use App\UI\IRenderable;

class Navbar implements IRenderable {
    private array $links;
    private TemplateObject $template;
    private UserEntity $user;
    private array $hideLinks;
    private int $mode;

    public function __construct(int $mode, UserEntity $user) {
        $this->mode = $mode;
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
        $this->user = $user;
        $this->hideLinks = [];

        $this->getLinks();
    }

    public function hideLink(string $title) {
        $this->hideLinks[] = $title;
    }

    private function getLinks() {
        switch($this->mode) {
            case NavbarModes::SUPERADMINISTRATION:
                $this->links = NavbarSuperAdminLinks::toArray();
                break;
            
            case NavbarModes::SUPERADMINISTRATION_SETTINGS:
                $this->links = NavbarSuperAdminSettingsLinks::toArray();
                break;

            case NavbarModes::GENERAL:
                $this->links = NavbarGeneralLinks::toArray();
                break;
        }
    }

    private function beforeRender() {
        $linksCode = '';

        foreach($this->links as $title => $link) {
            if(!in_array($title, $this->hideLinks)) {
                $linksCode .= $this->createLink($link, $title);
            }
        }

        $this->template->links = $linksCode;

        $userInfoLinks = [
            $this->user->getFullname() => $this->getUserProfileLink(),
            'Logout' => $this->getUserLogoutLink()
        ];

        $userInfo = '';
        foreach($userInfoLinks as $title => $link) {
            if(!in_array($title, $this->hideLinks)) {
                $userInfo .= $link;
            }
        }

        $this->template->user_info = $userInfo;
    }

    private function getUserProfileLink() {
        $link = null;
        switch($this->mode) {
            case NavbarModes::SUPERADMINISTRATION:
            case NavbarModes::SUPERADMINISTRATION_SETTINGS:
                $link = NavbarSuperAdminLinks::USER_PROFILE;
                break;

            case NavbarModes::GENERAL:
                $link = NavbarGeneralLinks::USER_PROFILE;
                break;
        }

        if($link === null) {
            return '';
        }

        return $this->createLink($link, $this->user->getFullname());
    }

    private function getUserLogoutLink() {
        $link = null;
        switch($this->mode) {
            case NavbarModes::SUPERADMINISTRATION:
            case NavbarModes::SUPERADMINISTRATION_SETTINGS:
                $link = NavbarSuperAdminLinks::USER_LOGOUT;
                break;

            case NavbarModes::GENERAL:
                $link = NavbarGeneralLinks::USER_LOGOUT;
                break;
        }

        if($link === null) {
            return '';
        }

        return $this->createLink($link, 'Logout');
    }

    private function createLink(array $url, string $title) {
        return '<a class="navbar-link" href="' . LinkHelper::createUrlFromArray($url) . '">' . $title . '</a>';
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }
}

?>