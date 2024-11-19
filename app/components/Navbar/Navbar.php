<?php

namespace App\Components\Navbar;

use App\Constants\Container\SystemGroups;
use App\Core\Application;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Entities\UserEntity;
use App\Helpers\LinkHelper;
use App\Managers\Container\GroupManager;
use App\Modules\TemplateObject;
use App\UI\IRenderable;

class Navbar implements IRenderable {
    private array $links;
    private TemplateObject $template;
    private UserEntity $user;
    private array $hideLinks;
    private int $mode;
    private Application $app;
    private ?GroupManager $groupManager;
    private CacheFactory $cacheFactory;

    public function __construct(int $mode, UserEntity $user, Application $app, ?GroupManager $groupManager) {
        $this->mode = $mode;
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
        $this->user = $user;
        $this->hideLinks = [];
        $this->app = $app;
        $this->groupManager = $groupManager;
        $this->cacheFactory = new CacheFactory();
    }

    public function inject(GroupManager $groupManager) {
        $this->groupManager = $groupManager;
    }

    public function startup() {
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
                $links = NavbarGeneralLinks::toArray();

                if($this->app->groupManager->isUserMemberOfSuperadministrators($this->user->getId()) || ($this->groupManager !== null && in_array($this->user->getId(), $this->groupManager->getUsersForGroupTitle(SystemGroups::ADMINISTRATORS)))) {
                    $links['Administration'] = NavbarGeneralLinks::A_SETTINGS;
                }

                $this->links = $links;
                break;

            case NavbarModes::ADMINISTRATION:
                $this->links = NavbarAdminLinks::toArray();

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

        $containerSwitch = $this->getContainerSwitch();
        if($containerSwitch !== null) {
            $userInfoLinks = array_merge(['Containers' => $containerSwitch], $userInfoLinks);
        }

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
            case NavbarModes::ADMINISTRATION:
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
            case NavbarModes::ADMINISTRATION:
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

    private function getContainerSwitch() {
        $navbarMemberships = $this->cacheFactory->getCache(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS);

        $count = $navbarMemberships->load($this->app->currentUser->getId(), function() {
            $memberships = $this->app->groupManager->getMembershipsForUser($this->app->currentUser->getId());

            $count = 0;
            foreach($memberships as $membership) {
                if(str_contains($membership->title, ' - users') || $membership->title == \App\Constants\SystemGroups::SUPERADMINISTRATORS) {
                    $count++;
                }
            }

            return $count;
        });

        if($count > 1) {
            return $this->createLink(['page' => 'Anonym:Login', 'action' => 'switchContainer'], 'Containers');
        } else {
            return null;
        }
    }
}

?>