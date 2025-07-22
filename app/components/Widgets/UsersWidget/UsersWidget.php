<?php

namespace App\Components\Widgets\UsersWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\Repositories\Container\GroupRepository;

/**
 * Widget with user statistics
 * 
 * @author Lukas Velek
 */
class UsersWidget extends Widget {
    private GroupRepository $groupRepository;
    private string $containerId;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param GroupRepository $userRepository UserRepository instance
     * @param string $containerId Container ID
     */
    public function __construct(HttpRequest $request, GroupRepository $groupRepository, string $containerId) {
        parent::__construct($request);

        $this->groupRepository = $groupRepository;
        $this->containerId = $containerId;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();
        
        $this->setData($data);
        $this->setTitle('Users');
        $this->enableRefresh();
    }

    /**
     * Processes data and defines rows
     * 
     * @return array<string, mixed> Processed data
     */
    private function processData() {
        $data = $this->fetchDataFromDb();

        return [
            'All users' => $data['total']
        ];
    }

    /**
     * Fetches data from the database
     * 
     * @return array<string, mixed> Data from the database
     */
    private function fetchDataFromDb() {
        $total = $this->fetchTotalUserCountFromDb();

        return [
            'total' => $total
        ];
    }

    /**
     * Fetches total user count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalUserCountFromDb() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        $qb = $this->app->userRepository->composeQueryForUsers();
        $qb->select(['COUNT(*) AS cnt'])
            ->where($qb->getColumnInValues('userId', $groupUsers))
            ->regenerateSQL();

        return $qb->execute()->fetch('cnt');
    }
}

?>