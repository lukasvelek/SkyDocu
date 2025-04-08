<?php

namespace App\Components\Widgets\AboutContainerWidget;

use App\Components\Widgets\Widget;
use App\Constants\ContainerEnvironments;
use App\Core\Http\HttpRequest;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\ContainerManager;
use App\Managers\GroupManager;
use App\Managers\UserManager;

class AboutContainerWidget extends Widget {
    private ContainerManager $containerManager;
    private GroupManager $groupManager;
    private UserManager $userManager;
    private string $containerId;

    public function __construct(HttpRequest $request, ContainerManager $containerManager, GroupManager $groupManager, UserManager $userManager, string $containerId) {
        parent::__construct($request);

        $this->containerManager = $containerManager;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
        $this->containerId = $containerId;

        $this->componentName = 'AboutContainerWidget';
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);

        $this->setTitle('About container');
    }

    private function processData() {
        $container = $this->getContainer();

        $groupUsers = $this->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');
        $groupGeneralUsers = [];
        $groupTechnicalUsers = [];
        foreach($groupUsers as $userId) {
            $user = $this->userManager->getUserById($userId);
            if($user->isTechnical()) {
                $groupTechnicalUsers[] = $user;
            } else {
                $groupGeneralUsers[] = $user;
            }
        }
        
        $data = [
            'Container ID' => $this->containerId,
            'Container title' => $container->getTitle(),
            'Container users / technical users' => (count($groupGeneralUsers) . ' / ' . count($groupTechnicalUsers)),
            'Date created' => DateTimeFormatHelper::formatDateToUserFriendly($container->getDateCreated()),
            'Container environment' => ContainerEnvironments::toString($container->getEnvironment())
        ];

        if($container->canShowContainerReferent()) {
            $user = $this->userManager->getUserById($container->getUserId());
            $data['Container referent'] = $user->getFullname();
        }

        return $data;
    }

    private function getContainer() {
        return $this->containerManager->getContainerById($this->containerId);
    }
}

?>