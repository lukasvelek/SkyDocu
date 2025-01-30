<?php

namespace App\Components\Widgets\UsersWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\Repositories\UserRepository;

/**
 * Widget with user statistics
 * 
 * @author Lukas Velek
 */
class UsersWidget extends Widget {
    private UserRepository $userRepository;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param UserRepository $userRepository UserRepository instance
     */
    public function __construct(HttpRequest $request, UserRepository $userRepository) {
        parent::__construct($request);

        $this->userRepository = $userRepository;
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
        $qb = $this->userRepository->composeQueryForUsers();
        $qb->select(['COUNT(*) AS cnt']);

        return $qb->execute()->fetch('cnt');
    }
}

?>