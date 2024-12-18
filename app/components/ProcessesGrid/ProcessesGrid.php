<?php

namespace App\Components\ProcessesGrid;

use App\Constants\Container\GridNames;
use App\Constants\Container\ProcessesGridSystemMetadata;
use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\ProcessStatus;
use App\Constants\Container\SystemProcessTypes;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\Helpers\ProcessHelper;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GridManager;
use App\Managers\Container\ProcessManager;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

/**
 * ProcessesGrid is an extension to GridBuilder and it is used for displaying processes
 * 
 * @author Lukas Velek
 */
class ProcessesGrid extends GridBuilder implements IGridExtendingComponent {
    private string $currentUserId;
    private GridManager $gridManager;
    private ProcessGridDataSourceHelper $dsHelper;
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

        $this->app = $app;
        $this->gridManager = $gridManager;
        $this->currentUserId = $app->currentUser->getId();
        $this->processManager = $processManager;
        $this->documentManager = $documentManager;

        $this->dsHelper = new ProcessGridDataSourceHelper($this->processManager->pr);

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

    protected function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();
        
        $this->appendSystemMetadata();

        $this->appendActions();

        $this->setup();

        parent::prerender();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->setGridName(GridNames::PROCESS_GRID . '_' . $this->view);
    }

    public function createDataSource() {
        $qb = $this->dsHelper->composeQuery($this->view, $this->currentUserId);

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
     * Appends system metadata to grid
     */
    private function appendSystemMetadata() {
        $metadata = $this->dsHelper->getMetadataToAppendForView($this->view);

        foreach($metadata as $name) {
            $text = ProcessesGridSystemMetadata::toString($name);

            switch($name) {
                case ProcessesGridSystemMetadata::AUTHOR_USER_ID:
                case ProcessesGridSystemMetadata::CURRENT_OFFICER_USER_ID:
                    $this->addColumnUser($name, $text);
                    break;

                case ProcessesGridSystemMetadata::DATE_CREATED:
                    $this->addColumnDatetime($name, $text);
                    break;

                case ProcessesGridSystemMetadata::DOCUMENT_ID:
                    $dataSource = clone $this->filledDataSource;

                    $documentIds = [];
                    while($row = $dataSource->fetchAssoc()) {
                        $documentIds[] = $row['documentId'];
                    }

                    $documentTitles = $this->getDocumentTitlesByIds($documentIds);

                    $col = $this->addColumnText($name, $text);
                    $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($documentTitles) {
                        $el = HTML::el('span')
                                ->text($documentTitles[$value]);

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
        return SystemProcessTypes::gridToString($key);
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
}

?>