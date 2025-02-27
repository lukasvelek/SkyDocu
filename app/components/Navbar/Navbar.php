<?php

namespace App\Components\Navbar;

use App\Constants\Container\SystemGroups;
use App\Constants\ContainerStatus;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Managers\Container\GroupManager;
use App\Modules\TemplateObject;
use App\UI\AComponent;
use App\UI\LinkBuilder;

/**
 * Navigation bar or navbar is the top "bar" that contains links to different parts of the application
 * 
 * @author Lukas Velek
 */
class Navbar extends AComponent {
    private array $links;
    private TemplateObject $template;
    private UserEntity $user;
    private array $hideLinks;
    private ?int $mode;
    private ?GroupManager $groupManager;
    private CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param HttpRequest $httpRequest HttpRequest instance
     * @param int $mode Navbar mode
     * @param UserEntity $user Current user entity
     * @param ?GroupManager Container GroupManager instance
     */
    public function __construct(HttpRequest $httpRequest, ?int $mode, UserEntity $user, ?GroupManager $groupManager) {
        parent::__construct($httpRequest);

        $this->mode = $mode;
        $this->links = [];
        $this->template = new TemplateObject(file_get_contents(__DIR__ . '\\template.html'));
        $this->user = $user;
        $this->hideLinks = [];
        $this->groupManager = $groupManager;
        $this->cacheFactory = new CacheFactory();
    }

    /**
     * Injects classes
     * 
     * @param GroupManager $groupManager Container GroupManager instance
     */
    public function inject(GroupManager $groupManager) {
        $this->groupManager = $groupManager;
    }

    public function startup() {
        parent::startup();

        $this->getLinks();
    }

    /**
     * Hides link
     * 
     * @param string $title Link title
     */
    public function hideLink(string $title) {
        $this->hideLinks[] = $title;
    }

    /**
     * Gets all links to render
     */
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

            default:
                break;
        }
    }

    /**
     * Prepares links and fills the template
     */
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
        if($containerSwitch !== null && $this->mode !== null) {
            $userInfoLinks = array_merge(['Containers' => $containerSwitch], $userInfoLinks);
        }

        $userInfo = '';
        if($this->mode !== null) {
            foreach($userInfoLinks as $title => $link) {
                if(!in_array($title, $this->hideLinks)) {
                    $userInfo .= $link;
                }
            }
        }

        $this->template->user_info = $userInfo;
    }

    /**
     * Returns user profile link for different sections of the application
     * 
     * @return string User profile link HTML code
     */
    private function getUserProfileLink() {
        $link = null;
        switch($this->mode) {
            case NavbarModes::GENERAL:
            case NavbarModes::ADMINISTRATION:
                $link = NavbarGeneralLinks::USER_PROFILE;
                break;

            default:
                break;
        }

        if($link === null) {
            return '<span class="navbar-link" style="cursor: pointer" title="' . $this->user->getFullname() . '">' . $this->user->getFullname() . '</span>';
        }

        return $this->createLink($link, $this->user->getFullname());
    }

    /**
     * Returns user logout link for different sections of the application
     * 
     * @return string User logout link HTML code
     */
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

            default:
                break;
        }

        if($link === null) {
            return '';
        }

        return $this->createLink($link, 'Logout');
    }

    /**
     * Creates HTML link code for given parameters
     * 
     * @param array $url Link URL
     * @param string $title Link title
     * @return string HTML code
     */
    private function createLink(array $url, string $title) {
        return '<a class="navbar-link" href="' . LinkBuilder::convertUrlArrayToString($url) . '" title="' . $title . '">' . $title . '</a>';
    }

    public function render() {
        $this->beforeRender();

        return $this->template->render()->getRenderedContent();
    }

    /**
     * Returns HTML code for the container switch
     * 
     * @return string HTML code
     */
    private function getContainerSwitch() {
        $navbarMemberships = $this->cacheFactory->getCache(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS);

        $count = $navbarMemberships->load($this->app->currentUser->getId(), function() {
            $memberships = $this->app->groupManager->getMembershipsForUser($this->app->currentUser->getId());

            $count = 0;
            foreach($memberships as $membership) {
                if(str_contains($membership->title, ' - users')) {
                    $container = $this->app->containerManager->getContainerById($membership->containerId);

                    if($container->getStatus() == ContainerStatus::RUNNING) {
                        $count++;
                    }
                } else if($membership->title == \App\Constants\SystemGroups::SUPERADMINISTRATORS) {
                    $count++;
                }
            }

            return $count;
        });

        if($count > 1) {
            $url = ['page' => 'Anonym:Login', 'action' => 'switchContainer'];

            return $this->createLink($url, 'Containers');
        } else {
            return null;
        }
    }

    /**
     * Revalidates containers available for switching between
     */
    public function revalidateContainerSwitch() {
        $navbarMemberships = $this->cacheFactory->getCache(CacheNames::NAVBAR_CONTAINER_SWITCH_USER_MEMBERSHIPS);
        $navbarMemberships->invalidate();

        $count = $navbarMemberships->load($this->app->currentUser->getId(), function() {
            $memberships = $this->app->groupManager->getMembershipsForUser($this->app->currentUser->getId(), true);

            $count = 0;
            foreach($memberships as $membership) {
                if(str_contains($membership->title, ' - users')) {
                    $container = $this->app->containerManager->getContainerById($membership->containerId, true);

                    if($container->getStatus() == ContainerStatus::RUNNING) {
                        $count++;
                    }
                } else if($membership->title == \App\Constants\SystemGroups::SUPERADMINISTRATORS) {
                    $count++;
                }
            }

            return $count;
        });
    }

    public static function createFromComponent(AComponent $component) {}
}

?>