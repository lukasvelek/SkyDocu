<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\SystemServiceStatus;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class BackgroundServicesPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('BackgroundServicesPresenter', 'Background services');
    }

    public function handleList() {}

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentBgServicesGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();
        
        $grid->createDataSourceFromQueryBuilder($this->app->systemServicesRepository->composeQueryForServices(), 'serviceId');
        $grid->setGridName(GridHelper::GRID_BACKGROUND_SERVICES);

        $grid->addColumnText('title', 'Title');
        $grid->addColumnDatetime('dateStarted', 'Service started');
        $grid->addColumnDatetime('dateEnded', 'Service ended');
        $col = $grid->addColumnConst('status', 'Status', SystemServiceStatus::class);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span')
                ->text($value);

            if($row->status == SystemServiceStatus::NOT_RUNNING) {
                $el->style('color', 'red');
            } else if($row->status == SystemServiceStatus::RUNNING) {
                $el->style('color', 'green');
            }

            return $el;
        };

        $run = $grid->addAction('run');
        $run->setTitle('Run');
        $run->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return $row->status == 1;
        };
        $run->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createURLString('run', ['serviceId' => $primaryKey]))
                ->text('Run');

            return $el;
        };

        return $grid;
    }

    public function handleRun() {
        $serviceId = $this->httpGet('serviceId', true);

        try {
            $service = $this->app->systemServicesRepository->getServiceById($serviceId);

            if($service === null) {
                throw new GeneralException('Service does not exist.');
            }

            if(!$this->app->serviceManager->runService($service->getScriptPath())) {
                throw new GeneralException('Could not run service.');
            }

            sleep(2);

            $this->flashMessage('Service run.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not run service. Reason: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect($this->createURL('list'));
    }
}

?>