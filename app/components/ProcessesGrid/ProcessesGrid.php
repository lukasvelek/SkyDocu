<?php

namespace App\Components\ProcessesGrid;

use App\Constants\Container\GridNames;
use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\ProcessStatus;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemProcessTypes;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\ProcessHelper;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GridManager;
use App\Managers\Container\ProcessManager;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Filter;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use QueryBuilder\QueryBuilder;

/**
 * ProcessesGrid is an extension to GridBuilder and it is used for displaying processes
 * 
 * @author Lukas Velek
 */
class ProcessesGrid extends GridBuilder implements IGridExtendingComponent {
    private string $currentUserId;
    private GridManager $gridManager;
    private ProcessGridDataSourceHelper $dataSourceHelper;
    private string $view;
    private ProcessManager $processManager;
    private DocumentManager $documentManager;

    /**
     * Class constructor
     * 
     * @param GridBuilder $grid GridBuilder instance
     * @param Application $app Application instance
     * @param GridManager $gridManager GridManager instance
     * @param ProcessManager $processManager ProcessManager instance
     * @param DocumentManager $documentManager DocumentManager instance
     */
    public function __construct(
        GridBuilder $grid,
        Application $app,
        GridManager $gridManager,
        ProcessManager $processManager,
        DocumentManager $documentManager
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);

        $this->app = $app;
        $this->gridManager = $gridManager;
        $this->currentUserId = $app->currentUser->getId();
        $this->processManager = $processManager;
        $this->documentManager = $documentManager;

        $this->dataSourceHelper = new ProcessGridDataSourceHelper($this->processManager->processRepository);

        $this->view = ProcessGridViews::VIEW_ALL;
    }

    /**
     * Sets the custom view
     * 
     * @param string $view Custom view
     */
    public function setView(string $view) {
        $this->view = $view;
    }

    public function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();
        
        $this->appendSystemMetadata();

        $this->appendActions();

        $this->appendFilters();

        $this->setup();

        parent::prerender();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->setGridName(GridNames::PROCESS_GRID . '_' . $this->view);
        $this->addQueryDependency('view', $this->view);
    }

    public function createDataSource() {
        $qb = $this->dataSourceHelper->composeQuery($this->view, $this->currentUserId);

        $this->createDataSourceFromQueryBuilder($qb, 'processId');
    }

    /**
     * Appends actions to grid
     */
    private function appendActions() {
        $open = $this->addAction('open');
        $open->setTitle('Open');
        $open->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            switch($this->view) {
                case ProcessGridViews::VIEW_WAITING_FOR_ME:
                case ProcessGridViews::VIEW_WITH_ME:
                case ProcessGridViews::VIEW_STARTED_BY_ME:
                    return true;

                case ProcessGridViews::VIEW_ALL:
                case ProcessGridViews::VIEW_FINISHED:
                    if($row->authorUserId == $this->currentUserId) {
                        return true;
                    } else {
                        return ProcessHelper::isUserInProcessWorkflow($this->currentUserId, $row);
                    }
                    break;
            }

            return false;
        };
        $open->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $params = [
                'processId' => $primaryKey,
                'backView' => $this->view
            ];

            $el = HTML::el('a');
            $el->href($this->createFullURLString('User:Processes', 'profile', $params))
                ->text('Open')
                ->class('grid-link');

            return $el;
        };
    }

    /**
     * Appends current officer to the grid. If the current officer has a substitute and this substitute is set in "currentOfficerSubstituteUserId" column, they are also displayed.
     * 
     * @param string $colName Column name
     * @param string $colTitle Column title
     */
    private function appendCurrentOfficer(string $colName, string $colTitle) {
        $col = $this->addColumnUser($colName, $colTitle);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if($row->currentOfficerSubstituteUserId !== null) {
                $username = '-';
                try {
                    $user = $this->app->userManager->getUserById($row->currentOfficerSubstituteUserId);
                    $username = $user->getFullname();
                } catch(AException $e) {}

                $el = HTML::el('span');

                $el->text($value . ' (<i title="Current officer\'s substitute">' . $username . '</i>)');

                return $el;
            }
        };
    }

    /**
     * Appends system metadata to grid
     */
    private function appendSystemMetadata() {
        $metadata = $this->dataSourceHelper->getMetadataToAppendForView($this->view);

        foreach($metadata as $name) {
            $text = ProcessesGridSystemMetadata::toString($name);

            switch($name) {
                case ProcessesGridSystemMetadata::AUTHOR_USER_ID:
                    $this->addColumnUser($name, $text);
                    break;

                case ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID:
                    $this->appendCurrentOfficer($name, $text);

                    break;

                case ProcessesGridSystemMetadata::DATE_CREATED:
                    $this->addColumnDatetime($name, $text);
                    break;

                case ProcessesGridSystemMetadata::DOCUMENT_ID:
                    $dataSource = $this->filledDataSource;

                    $documentIds = [];
                    while($row = $dataSource->fetchAssoc()) {
                        $documentId = $row['documentId'];
                        
                        if($documentId !== null) {
                            $documentIds[] = $row['documentId'];
                        }
                    }

                    $documentTitles = $this->getDocumentTitlesByIds($documentIds);

                    $col = $this->addColumnText($name, $text);
                    $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($documentTitles) {
                        $el = HTML::el('span');

                        if(in_array($value, $documentTitles)) {
                            $el->text($documentTitles[$value]);
                        } else {
                            $el->text('-');
                        }

                        return $el;
                    };
                    $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($documentTitles) {
                        return $documentTitles[$value];
                    };
                    break;

                case ProcessesGridSystemMetadata::TYPE:
                    $col = $this->addColumnText($name, $text);
                    $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                        $type = $this->getTypeByKey($value);

                        $el = HTML::el('span')
                                ->text($type);

                        if($type != '#ERROR' && !array_key_exists($value, SystemProcessTypes::getAll())) {
                            $fgColor = StandaloneProcesses::getColor($value);
                            $bgColor = StandaloneProcesses::getBackgroundColor($value);

                            $el->style('color', $fgColor)
                                ->style('background-color', $bgColor)
                                ->style('border-radius', '10px')
                                ->style('padding', '5px');
                        }

                        return $el;
                    };
                    $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                        $type = $this->getTypeByKey($value);

                        return $type;
                    };
                    break;

                case ProcessesGridSystemMetadata::STATUS:
                    $this->addColumnConst($name, $text, ProcessStatus::class);
                    break;
            }
        }
    }

    /**
     * Gets process type's grid title by it's key
     * 
     * @param string $key Process type key
     * @return string Process type's grid title
     */
    private function getTypeByKey(string $key) {
        $result = SystemProcessTypes::gridToString($key);

        if($result !== null) {
            return $result;
        }

        $result = StandaloneProcesses::toString($key);

        if($result !== null) {
            return $result;
        }

        return '#ERROR';
    }

    /**
     * Returns array of document titles for given document IDs
     * 
     * @param array $documentIds Document IDs
     * @return array<string, string> Documents titles or #ERROR on error
     */
    private function getDocumentTitlesByIds(array $documentIds) {
        $data = [];

        $dbData = $this->documentManager->getDocumentsByIds($documentIds);

        foreach($documentIds as $documentId) {
            if(!array_key_exists($documentId, $dbData)) {
                $data[$documentId] = '#ERROR';
            } else {
                $data[$documentId] = $dbData[$documentId]->title;
            }
        }

        return $data;
    }

    /**
     * Appends filters to the grid
     */
    private function appendFilters() {
        // Common
        $this->addFilter(ProcessesGridSystemMetadata::TYPE, null, StandaloneProcesses::getAll());
        $this->addFilter(ProcessesGridSystemMetadata::STATUS, null, ProcessStatus::getAll());
        
        $documentFilter = $this->addFilter(ProcessesGridSystemMetadata::DOCUMENT_ID, null, $this->getDocumentsInGrid());
        $documentFilter->onSqlExecute[] = function(QueryBuilder &$qb, Filter $filter) {
            if($filter->currentValue == 'empty') {
                $qb->andWhere(ProcessesGridSystemMetadata::DOCUMENT_ID . ' IS NULL');
            }
        };
        
        // Current officer
        if($this->view != ProcessGridViews::VIEW_WAITING_FOR_ME) {
            $this->addFilter(ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID, null, $this->getCurrentOfficersInGrid());
        }

        // Author
        if($this->view != ProcessGridViews::VIEW_STARTED_BY_ME) {
            $this->addFilter(ProcessesGridSystemMetadata::AUTHOR_USER_ID, null, $this->getAuthorsInGrid());
        }
    }

    /**
     * Retirms all documents in grid
     * 
     * @return array Documents
     */
    private function getDocumentsInGrid() {
        $qb = $this->getPagedDataSource();

        $qb->execute();

        $documents = [];
        while($row = $qb->fetchAssoc()) {
            $documentId = $row[ProcessesGridSystemMetadata::DOCUMENT_ID];

            if($documentId === null) {
                continue;
            } else if(array_key_exists($documentId, $documents)) {
                continue;
            }

            try {
                $document = $this->documentManager->getDocumentById($documentId, false);
            } catch(AException $e) {
                continue;
            }

            $documents[$documentId] = $document->title;
        }

        $documents['empty'] = 'None';

        return $documents;
    }

    /**
     * Returns all current officers in grid
     * 
     * @return array Current officers
     */
    private function getCurrentOfficersInGrid() {
        $qb = $this->getPagedDataSource();

        $qb->execute();

        $currentOfficers = [];
        while($row = $qb->fetchAssoc()) {
            $officerId = $row[ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID];

            if($officerId === null) {
                continue;
            }

            if(array_key_exists($officerId, $currentOfficers)) {
                continue;
            }

            try {
                $officer = $this->app->userManager->getUserById($officerId);
            } catch(AException $e) {
                continue;
            }

            $currentOfficers[$officerId] = $officer->getFullname();
        }

        return $currentOfficers;
    }

    /**
     * Returns all authors in grid
     * 
     * @return array Authors
     */
    private function getAuthorsInGrid() {
        $qb = $this->getPagedDataSource();

        $qb->execute();

        $authors = [];
        while($row = $qb->fetchAssoc()) {
            $authorId = $row[ProcessesGridSystemMetadata::AUTHOR_USER_ID];

            if($authorId === null) {
                continue;
            }

            if(array_key_exists($authorId, $authors)) {
                continue;
            }

            try {
                $author = $this->app->userManager->getUserById($authorId);
            } catch(AException $e) {
                continue;
            }

            $authors[$authorId] = $author->getFullname();
        }

        return $authors;
    }

    public function actionFilter() {
        $this->prerender();

        return parent::actionFilter();
    }

    public function actionFilterClear() {
        $this->clearActiveFilters();

        $this->prerender();

        return parent::actionFilterClear();
    }
}

?>