<?php

namespace App\Components\Widgets\ContainerStatsWidget;

use App\Components\Widgets\Widget;
use App\Constants\ContainerStatus;
use App\Core\Http\HttpRequest;
use App\Managers\ContainerManager;

/**
 * Widget with container statistics
 * 
 * @author Lukas Velek
 */
class ContainerStatsWidget extends Widget {
    private ContainerManager $containerManager;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param ContainerManager $containerManager ContainerManager instance
     */
    public function __construct(HttpRequest $request, ContainerManager $containerManager) {
        parent::__construct($request);

        $this->containerManager = $containerManager;

        $this->componentName = 'ContainerStatsWidget';
    }

    public function startup() {
        parent::startup();

        $data = $this->processData();

        $this->setData($data);
        $this->setTitle('Container statistics');
        $this->enableRefresh();
    }

    /**
     * Processes widget data
     * 
     * @return array Widget rows
     */
    private function processData() {
        $data = $this->fetchDataFromDb();

        $rows = [
            'All containers' => $data['totalCount'],
            'New containers' => $data['newCount'],
            'Running containers' => $data['runningCount'],
            'Not running containers' => $data['notRunningCount'],
            'Requested containers' => $data['requestedCount']
        ];

        return $rows;
    }

    /**
     * Fetches data from the database
     * 
     * @param array Data rows
     */
    private function fetchDataFromDb() {
        $totalCount = $this->fetchTotalContainerCountFromDb();
        $newCount = $this->fetchNewContainerCountFromDb();
        $runningCount = $this->fetchRunningContainerCountFromDb();
        $notRunningCount = $this->fetchNotRunningContainerCountFromDb();
        $requestedCount = $this->fetchRequestedContainerCountFromDb();

        return [
            'totalCount' => $totalCount,
            'newCount' => $newCount,
            'runningCount' => $runningCount,
            'notRunningCount' => $notRunningCount,
            'requestedCount' => $requestedCount
        ];
    }

    /**
     * Fetches total container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchTotalContainerCountFromDb(): mixed {
        $json = [
            'operation' => 'query',
            'name' => 'getContainers',
            'definition' => [
                'containers' => [
                    'get' => [
                        'cols' => [
                            'containerId'
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->executePeeQL($json);

        return count($result['data']);
    }

    /**
     * Fetches new container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNewContainerCountFromDb(): mixed {
        $json = [
            'operation' => 'query',
            'name' => 'getContainers',
            'definition' => [
                'containers' => [
                    'get' => [
                        'cols' => [
                            'containerId'
                        ],
                        'conditions' => [
                            [
                                'col' => 'status',
                                'value' => ContainerStatus::NEW,
                                'type' => 'eq'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->executePeeQL($json);

        return count($result['data']);
    }

    /**
     * Fetches running container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchRunningContainerCountFromDb(): mixed {
        $json = [
            'operation' => 'query',
            'name' => 'getContainers',
            'definition' => [
                'containers' => [
                    'get' => [
                        'cols' => [
                            'containerId'
                        ],
                        'conditions' => [
                            [
                                'col' => 'status',
                                'value' => ContainerStatus::RUNNING,
                                'type' => 'eq'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->executePeeQL($json);

        return count($result['data']);
    }

    /**
     * Fetches not running container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchNotRunningContainerCountFromDb(): mixed {
        $json = [
            'operation' => 'query',
            'name' => 'getContainers',
            'definition' => [
                'containers' => [
                    'get' => [
                        'cols' => [
                            'containerId'
                        ],
                        'conditions' => [
                            [
                                'col' => 'status',
                                'value' => ContainerStatus::NOT_RUNNING,
                                'type' => 'eq'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->executePeeQL($json);

        return count($result['data']);
    }

    /**
     * Fetches requested container count from the database
     * 
     * @return mixed Data from the database
     */
    private function fetchRequestedContainerCountFromDb(): mixed {
        $json = [
            'operation' => 'query',
            'name' => 'getContainers',
            'definition' => [
                'containers' => [
                    'get' => [
                        'cols' => [
                            'containerId'
                        ],
                        'conditions' => [
                            [
                                'col' => 'status',
                                'value' => ContainerStatus::REQUESTED,
                                'type' => 'eq'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->executePeeQL($json);

        return count($result['data']);
    }

    public function actionRefresh() {
        $data = $this->processData();
        $this->setData($data);

        return parent::actionRefresh();
    }
}

?>