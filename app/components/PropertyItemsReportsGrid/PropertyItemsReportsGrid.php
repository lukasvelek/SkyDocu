<?php

namespace App\Components\PropertyItemsReportsGrid;

use App\Constants\Container\GridNames;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\Container\StandaloneProcessManager;
use App\Repositories\Container\PropertyItemsRepository;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class PropertyItemsReportsGrid extends GridBuilder implements IGridExtendingComponent {
    private string $view;
    private string $currentUserId;
    private bool $isMy;
    private array $itemsCache;

    private PropertyItemsRepository $propertyItemsRepository;
    private StandaloneProcessManager $standaloneProcessManager;

    public function __construct(
        GridBuilder $grid,
        Application $app,
        PropertyItemsRepository $propertyItemsRepository,
        StandaloneProcessManager $standaloneProcessManager
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);

        $this->app = $app;
        $this->propertyItemsRepository = $propertyItemsRepository;
        $this->standaloneProcessManager = $standaloneProcessManager;

        $this->currentUserId = $this->app->currentUser->getId();
        $this->isMy = false;
        $this->itemsCache = [];
    }

    public function setView(string $view) {
        $this->view = $view;

        if(explode('-', $this->view)[1] == 'my') {
            $this->isMy = true;
        }
    }

    public function prerender() {
        $this->createDataSource();

        $this->appendSystemMetadata();

        $this->appendActions();

        $this->setup();

        parent::prerender();
    }

    public function createDataSource() {
        $qb = $this->propertyItemsRepository->composeQueryForPropertyItems();
        
        if($this->isMy) {
            $qb->andWhere('userId = ?', [$this->currentUserId]);
        }

        $this->createDataSourceFromQueryBuilder($qb, 'relationId');
    }

    private function setup() {
        $this->setGridName(GridNames::PROPERTY_ITEMS_REPORTS_GRID);
        $this->addQueryDependency('view', $this->view);
    }

    private function appendSystemMetadata() {
        if(!$this->isMy) {
            $this->addColumnUser('userId', 'User');
        }

        $col = $this->addColumnText('title', 'Title');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $item = $this->getItem($row->itemId);

            return $item['title'];
        };

        $col = $this->addColumnText('itemCode', 'Inventory number');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $item = $this->getItem($row->itemId);

            return $item['title2'];
        };

        $col = $this->addColumnText('registrationDate', 'Registration date');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $entry = $this->propertyItemsRepository->getFirstEntryForPropertyItem($row->itemId);

            if($entry !== null) {
                return DateTimeFormatHelper::formatDateToUserFriendly($entry['dateCreated'], $this->app->currentUser->getDatetimeFormat());
            } else {
                return null;
            }
        };
    }

    private function appendActions() {
        // history
        $history = $this->addAction('history');
        $history->setTitle('History');
        $history->onCanRender[] = function() {
            return !$this->isMy;
        };
        $history->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link')
                ->href($this->createFullURLString('User:PropertyItems', 'historyList', ['itemId' => $row->itemId]))
                ->text('History');

            return $el;
        };

        // remove
        $remove = $this->addAction('remove');
        $remove->setTitle('Remove');
        $remove->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->userId !== $this->currentUserId) {
                return false;
            }

            if($this->isInProcess($row->itemId)) {
                return false;
            }

            return true;
        };
        $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->class('grid-link')
                ->href($this->createFullURLString('User:PropertyItems', 'removeItem', ['itemId' => $row->itemId]))
                ->text('Remove');

            return $el;
        };
    }

    private function getAllPropertyItems() {
        if(empty($this->itemsCache)) {
            $items = $this->standaloneProcessManager->getProcessMetadataEnumValues(StandaloneProcesses::REQUEST_PROPERTY_MOVE, 'items');

            $tmp = [];
            foreach($items as $item) {
                $tmp[$item->valueId] = [
                    'title' => $item->title,
                    'title2' => $item->title2,
                    'title3' => $item->title3
                ];
            }

            $this->itemsCache = $tmp;
        }
    }

    private function getItem(string $itemId) {
        $this->getAllPropertyItems();

        return $this->itemsCache[$itemId];
    }

    private function isInProcess(string $itemId) {
        $items = $this->standaloneProcessManager->getProcessMetadataEnumValues(StandaloneProcesses::REQUEST_PROPERTY_MOVE, 'items');

        $item = null;
        foreach($items as $_item) {
            if($_item->valueId == $itemId) {
                $item = $_item;
            }
        }
        
        if($item === null) {
            return false;
        }

        $qb = $this->standaloneProcessManager->composeQueryForProcessData();
        $qb->where('data LIKE ?', ['%' . $item->title2 . '%'])
            ->execute();

        $processIds = [];
        while($row = $qb->fetchAssoc()) {
            $processIds[] = $row['processId'];
        }

        $qb = $this->standaloneProcessManager->processManager->processRepository->composeQueryForStandaloneProcesses();
        $qb->select(['COUNT(processId) AS cnt'])
            ->andWhere($qb->getColumnInValues('processId', $processIds))
            ->andWhere('status = 1')
            ->regenerateSQL()
            ->execute();

        $result = $qb->fetch('cnt');

        if($result === null) {
            return false;
        }

        if($result == 0) {
            return false;
        }

        return true;
    }
}

?>