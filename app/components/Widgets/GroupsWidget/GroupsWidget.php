<?php

namespace App\Components\Widgets\GroupsWidget;

use App\Components\Widgets\Widget;
use App\Core\Http\HttpRequest;
use App\Repositories\Container\GroupRepository;

/**
 * Widget with group statistics
 * 
 * @author Lukas Velek
 */
class GroupsWidget extends Widget {
    private GroupRepository $groupRepository;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param GroupRepository $groupRepository GroupRepository instance
     */
    public function __construct(HttpRequest $request, GroupRepository $groupRepository) {
        parent::__construct($request);

        $this->groupRepository = $groupRepository;
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();
        
        $this->setData($data);
        $this->setTitle('Groups');
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
            'All groups' => $data['total']
        ];
    }

    /**
     * Fetches data from the database
     * 
     * @return array<string, mixed> Data from the database
     */
    private function fetchDataFromDb() {
        $total = $this->fetchTotalGroupCountFromDb();

        return [
            'total' => $total
        ];
    }

    /**
     * Fetches total group count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalGroupCountFromDb() {
        $qb = $this->groupRepository->composeQueryForGroups();
        $qb->select(['COUNT(*) AS cnt']);

        return $qb->execute()->fetch('cnt');
    }
}

?>