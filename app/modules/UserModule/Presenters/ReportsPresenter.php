<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportsGrid\ProcessReportsGrid;
use App\Components\ProcessReportsSidebar\ProcessReportsSidebar;
use App\Components\PropertyItemsReportsGrid\PropertyItemsReportsGrid;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemGroups;
use App\Core\Http\HttpRequest;
use App\Helpers\LinkHelper;
use App\Repositories\Container\PropertyItemsRepository;
use App\UI\LinkBuilder;

class ReportsPresenter extends AUserPresenter {
    private ?string $view = null;

    public function __construct() {
        parent::__construct('ReportsPresenter', 'Reports');
    }

    public function handleList() {
        if($this->httpRequest->get('view') === null) {
            $processType = null;
            foreach($this->standaloneProcessManager->getEnabledProcessTypes() as $_processType) {
                if($_processType->typeKey != StandaloneProcesses::REQUEST_PROPERTY_MOVE) {
                    $processType = $_processType->typeKey;
                }
            }

            $view = $processType . '-my';
            $url = $this->httpRequest->getCurrentPageActionAsArray();
            $url['view'] = $view;
            $this->redirect($url);
        } else {
            $this->view = $this->httpRequest->get('view');
        }
    }

    public function renderList() {
        if($this->view == 'propertyItems-all' && $this->groupManager->isUserMemberOfGroupTitle($this->getUserId(), SystemGroups::PROPERTY_MANAGERS)) {
            $links = [
                LinkBuilder::createSimpleLink('New item', $this->createFullURL('User:PropertyItems', 'newPropertyItemForm'), 'link')
            ];

            $this->template->links = LinkHelper::createLinksFromArray($links);
        } else {
            $this->template->links = '';
        }
    }

    protected function createComponentProcessReportsSidebar(HttpRequest $request) {
        $sidebar = new ProcessReportsSidebar($request, $this->standaloneProcessManager, $this->supervisorAuthorizator);

        return $sidebar;
    }

    protected function createComponentReportGrid(HttpRequest $request) {
        $viewParts = $request->get('view');
        $viewParts = explode('-', $viewParts);

        if($viewParts[0] == 'propertyItems') {
            $grid = new PropertyItemsReportsGrid(
                $this->componentFactory->getGridBuilder($this->containerId),
                $this->app,
                new PropertyItemsRepository($this->gridRepository->conn, $this->logger, $this->app->transactionLogRepository),
                $this->standaloneProcessManager
            );

            $grid->setView($request->get('view'));
        } else {
            $grid = new ProcessReportsGrid(
                $this->componentFactory->getGridBuilder($this->containerId),
                $this->app,
                $this->processManager,
                $this->standaloneProcessManager
            );
    
            $grid->setView($viewParts[1]);
            $grid->setProcessType($viewParts[0]);
        }

        return $grid;
    }
}

?>