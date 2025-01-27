<?php

namespace App\Components\DocumentsGrid;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentsGridSystemMetadata;
use App\Constants\Container\DocumentStatus;
use App\Constants\Container\GridNames;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Enums\AEnumForMetadata;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Lib\Processes\ProcessFactory;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\EnumManager;
use App\Managers\Container\GridManager;
use App\Modules\APresenter;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

/**
 * DocumentsGrid is an extension to GridBuilder and it is used for displaying documents
 * 
 * @author Lukas Velek
 */
class DocumentsGrid extends GridBuilder implements IGridExtendingComponent {
    private string $currentUserId;
    private DocumentManager $documentManager;
    private DocumentBulkActionsHelper $documentBulkActionsHelper;
    private DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    private GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    private EnumManager $enumManager;
    private GridManager $gridManager;
    private ProcessFactory $processFactory;

    private bool $allMetadata;
    private ?string $currentFolderId;
    private bool $showDocumentInfoLink;
    private bool $showShared;

    /**
     * Class constructor
     * 
     * @param GridBuilder $grid GridBuilder instance
     * @param Application $app Application instance
     * @param DocumentManager $documentManager DocumentManager instance
     * @param DocumentBulkActionAuthorizator $documentBulkActionAuthorizator DocumentBulkActionAuthorizator instance
     * @param GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator
     * @param EnumManager $enumManager
     * @param GridManager $gridManager
     * @param ProcessFactory $processFactory
     * @param string $containerId
     */
    public function __construct(
        GridBuilder $grid,
        Application $app,
        DocumentManager $documentManager,
        DocumentBulkActionAuthorizator $documentBulkActionAuthorizator,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        EnumManager $enumManager,
        GridManager $gridManager,
        ProcessFactory $processFactory
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());

        $this->app = $app;
        $this->documentManager = $documentManager;
        $this->currentUserId = $app->currentUser->getId();
        $this->documentBulkActionAuthorizator = $documentBulkActionAuthorizator;
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->enumManager = $enumManager;
        $this->gridManager = $gridManager;
        $this->processFactory = $processFactory;

        $this->documentBulkActionsHelper = new DocumentBulkActionsHelper($this->app, $this->documentManager, $this->httpRequest, $this->documentBulkActionAuthorizator, $this->groupStandardOperationsAuthorizator, $this->processFactory);

        $this->allMetadata = false;
        $this->currentFolderId = null;
        $this->showDocumentInfoLink = true;
        $this->showShared = false;
    }

    /**
     * Sets the current folder
     * 
     * @param string $folderId Current folder ID
     */
    public function setCurrentFolder(?string $folderId) {
        $this->currentFolderId = $folderId;
    }

    /**
     * Sets if shared documents will be shown
     */
    public function setShowShared() {
        $this->showShared = true;
    }

    /**
     * Displays custom metadata
     */
    public function showCustomMetadata() {
        $this->allMetadata = true;
    }

    /**
     * Hides custom metadata and displays only system metadata
     */
    public function hideCustomMetadata() {
        $this->allMetadata = false;
    }

    /**
     * Displays document information page link
     */
    public function showDocumentInfo() {
        $this->showDocumentInfoLink = true;
    }

    /**
     * Hides document information page link
     */
    public function hideDocumentInfo() {
        $this->showDocumentInfoLink = false;
    }

    public function prerender() {
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();

        if($this->allMetadata && !$this->showShared) {
            $this->appendCustomMetadata();
        }

        $this->appendActions();

        $this->appendFilters();

        $this->setup();

        parent::prerender();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        if(!$this->showShared) {
            $this->addQueryDependency('folderId', $this->getFolderId());
        }
        $this->setGridName(GridNames::DOCUMENTS_GRID);
    }

    /**
     * Appends grid actions
     */
    private function appendActions() {
        if($this->showDocumentInfoLink) {
            $info = $this->addAction('info');
            $info->setTitle('Information');
            $info->onCanRender[] = function() {
                return true;
            };
            $info->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
                $el = HTML::el('a');
                $el->href($this->createFullURLString('User:Documents', 'info', ['documentId' => $primaryKey]))
                    ->text('Information')
                    ->class('grid-link');

                return $el;
            };
        }
    }

    public function createDataSource() {
        if($this->showShared) {
            $qb = $this->documentManager->composeQueryForSharedDocuments($this->currentUserId);
        } else {
            $folderId = $this->getFolderId();
            $qb = $this->documentManager->composeQueryForDocuments($this->currentUserId, $folderId, $this->allMetadata);
        }

        if($this->quickSearchQuery !== null) {
            $qb->andWhere('title LIKE :quickSearchTitle')
                ->setParams([':quickSearchTitle' => $this->quickSearchQuery]);
        }

        $this->createDataSourceFromQueryBuilder($qb, 'documentId');
    }

    /**
     * Returns current folder ID
     * 
     * @return string Current folder ID
     */
    private function getFolderId() {
        if($this->currentFolderId === null) {
            if($this->httpRequest->query('folderId') !== null) {
                $this->currentFolderId = $this->httpRequest->query('folderId');
            } else {
                throw new GeneralException('No folder is selected.');
            }
        }

        return $this->currentFolderId;
    }

    /**
     * Appends system metadata
     */
    private function appendSystemMetadata() {
        $config = $this->getGridConfiguration();

        if($config === null) {
            $config = array_keys(DocumentsGridSystemMetadata::getAll());
        }

        foreach($config as $column) {
            switch($column) {
                case DocumentsGridSystemMetadata::TITLE:
                    $this->addColumnText(DocumentsGridSystemMetadata::TITLE, DocumentsGridSystemMetadata::toString(DocumentsGridSystemMetadata::TITLE));
                    break;

                case DocumentsGridSystemMetadata::AUTHOR_USER_ID:
                    $this->addColumnUser(DocumentsGridSystemMetadata::AUTHOR_USER_ID, DocumentsGridSystemMetadata::toString(DocumentsGridSystemMetadata::AUTHOR_USER_ID));
                    break;

                case DocumentsGridSystemMetadata::STATUS:
                    $this->addColumnConst(DocumentsGridSystemMetadata::STATUS, DocumentsGridSystemMetadata::toString(DocumentsGridSystemMetadata::STATUS), DocumentStatus::class);
                    break;

                case DocumentsGridSystemMetadata::IS_IN_PROCESS:
                    $dataSource = clone $this->filledDataSource;

                    $documentIds = [];
                    while($row = $dataSource->fetchAssoc()) {
                        $documentIds[] = $row['documentId'];
                    }

                    $documentsInProcess = $this->processFactory->processManager->areDocumentsInProcesses($documentIds);

                    $col = $this->addColumnBoolean(DocumentsGridSystemMetadata::IS_IN_PROCESS, DocumentsGridSystemMetadata::toString(DocumentsGridSystemMetadata::IS_IN_PROCESS));
                    array_unshift($col->onRenderColumn, function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($documentsInProcess) {
                        return array_key_exists($row->documentId, $documentsInProcess);
                    });
                    break;
            }
        }
    }

    /**
     * Attempts to get grid configuration
     * 
     * @return ?array Array of visible system metadata or null if no configuration exists
     */
    private function getGridConfiguration() {
        $row = $this->gridManager->getGridConfigurationForGridName(GridNames::DOCUMENTS_GRID);

        if($row === null) {
            return null;
        }

        return explode(';', $row->columnConfiguration);
    }

    /**
     * Appends custom metadata - only metadata that is allowed in the current folder is appended
     */
    private function appendCustomMetadata() {
        /**
         * 1. get all custom metadata for current folder
         * 2. get their names
         * 3. get their values if their type is select
         * 4. get document values for the metadata
         * 5. replace their rendered value if their type is select
         */

        /**
         * @var array<string, \App\Core\DB\DatabaseRow> $customMetadatas
         */
        $customMetadatas = $this->documentManager->getCustomMetadataForFolder($this->getFolderId());

        $metadataSelectValues = [];
        $documentCustomMetadataValues = [];
        foreach($customMetadatas as $metadataId => $metadata) {
            if($metadata->type == CustomMetadataTypes::ENUM) {
                $metadataSelectValues[$metadata->title] = $this->documentManager->getMetadataValues($metadataId);
            }

            $qb = $this->documentManager->composeQueryForDocumentCustomMetadataValues();
            $qb->andWhere('metadataId = ?', [$metadataId])
                ->andWhere($qb->getColumnInValues('documentId', $this->getEntityIdsInGrid()))
                ->execute();

            while($row = $qb->fetchAssoc()) {
                $row = DatabaseRow::createFromDbRow($row);

                $documentCustomMetadataValues[$row->documentId][$metadata->title] = $row->value;
            }

            switch($metadata->type) {
                case CustomMetadataTypes::ENUM:
                    $this->appendEnumCustomMetadata($metadata, $metadataSelectValues, $documentCustomMetadataValues);
                    break;

                case CustomMetadataTypes::TEXT:
                    $this->appendTextCustomMetadata($metadata, $documentCustomMetadataValues);
                    break;

                case CustomMetadataTypes::DATETIME:
                    $this->appendDatetimeCustomMetadata($metadata, $documentCustomMetadataValues);
                    break;

                case CustomMetadataTypes::BOOL:
                    $this->appendBoolCustomMetadata($metadata, $documentCustomMetadataValues);
                    break;

                case CustomMetadataTypes::NUMBER:
                    $this->appendNumberCustomMetadata($metadata, $documentCustomMetadataValues);
                    break;

                default:
                    if($metadata->type >= 100) { // system custom enums
                        $this->appendSystemEnumCustomMetadata($metadata, $documentCustomMetadataValues);
                    }
                    break;
            }
        }
    }

    /**
     * Appends system enums
     * 
     * @param DatabaseRow $metadata Metadata database row
     * @param array $documentCustomMetadata Custom metadata values for documents
     */
    private function appendSystemEnumCustomMetadata(DatabaseRow $metadata, array $documentCustomMetadataValues) {
        $values = $this->enumManager->getMetadataEnumValuesByMetadataType($metadata);

        $col = $this->addColumnText($metadata->title, $metadata->guiTitle);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($values, $documentCustomMetadataValues, $metadata) {
            if(array_key_exists($row->documentId, $documentCustomMetadataValues)) {
                $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];

                if($values->keyExists($tmp)) {
                    return $values->get($tmp)[AEnumForMetadata::TITLE];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($metadata, $documentCustomMetadataValues, $values) {
            if(array_key_exists($row->documentId, $documentCustomMetadataValues)) {
                $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];

                if($values->keyExists($tmp)) {
                    return $values->get($tmp)[AEnumForMetadata::TITLE];
                } else {
                    return '-';
                }
            } else {
                return '-';
            }
        };
    }

    /**
     * Appends custom metadata that have type of boolean
     * 
     * @param DatabaseRow $metadata Metadata database row
     * @param array $documentCustomMetadataValues Custom metadata values for documents
     */
    private function appendBoolCustomMetadata(DatabaseRow $metadata, array $documentCustomMetadataValues) {
        $col = $this->addColumnText($metadata->title, $metadata->guiTitle);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($documentCustomMetadataValues, $metadata) {
            if(array_key_exists($row->documentId, $documentCustomMetadataValues) && array_key_exists($metadata->title, $documentCustomMetadataValues[$row->documentId])) {
                $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];

                if($tmp === true || $tmp == 1) {
                    $tmp = '&check;';
                    $color = 'green';
                } else {
                    $tmp = '&cross;';
                    $color = 'red';
                }
            } else {
                $color = 'black';
                return '-';
            }

            $el = HTML::el('span')
                ->title($tmp)
                ->text($tmp)
                ->style('color', $color);

            return $el;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($documentCustomMetadataValues, $metadata) {
            if(array_key_exists($row->documentId, $documentCustomMetadataValues) && array_key_exists($metadata->title, $documentCustomMetadataValues[$row->documentId])) {
                $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];
    
                if($tmp === true || $tmp == 1) {
                    return 'True';
                } else {
                    return 'False';
                }
            } else {
                return '-';
            }
        };
    }

    /**
     * Appends custom metadata that have type of number
     * 
     * @param DatabaseRow $metadata Metadata database row
     * @param array $documentCustomMetadataValues Custom metadata values for documents
     */
    private function appendNumberCustomMetadata(DatabaseRow $metadata, array $documentCustomMetadataValues) {
        return $this->appendTextCustomMetadata($metadata, $documentCustomMetadataValues);
    }

    /**
     * Appends custom metadata that have type of datetime
     * 
     * @param DatabaseRow $metadata Metadata database row
     * @param array $documentCustomMetadataValues Custom metadata values for documents
     */
    private function appendDatetimeCustomMetadata(DatabaseRow $metadata, array $documentCustomMetadataValues) {
        $col = $this->addColumnDatetime($metadata->title, $metadata->guiTitle);
        $tmp = function(DatabaseRow $row, mixed $value) use ($documentCustomMetadataValues, $metadata) {
            return $documentCustomMetadataValues[$row->documentId][$metadata->title];
        };
        
        array_unshift($col->onRenderColumn, $tmp);
        array_unshift($col->onExportColumn, $tmp);
    }

    /**
     * Appends custom metadata that have type of text
     * 
     * @param DatabaseRow $metadata Metadata database row
     * @param array $documentCustomMetadataValues Custom metadata values for documents
     */
    private function appendTextCustomMetadata(DatabaseRow $metadata, array $documentCustomMetadataValues) {
        $col = $this->addColumnText($metadata->title, $metadata->guiTitle);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($documentCustomMetadataValues, $metadata) {
            $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];

            $el = HTML::el('span')
                ->title($tmp)
                ->text($tmp);

            return $el;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($documentCustomMetadataValues, $metadata) {
            return $documentCustomMetadataValues[$row->documentId][$metadata->title];
        };
    }

    /**
     * Appends custom metadata that have type of enum
     * 
     * @param DatabaseRow $metadata Metadata database row
     * @param array $metadataSelectValues Custom metadata enum values
     * @param array $documentCustomMetadataValues Custom metadata values for documents
     */
    private function appendEnumCustomMetadata(DatabaseRow $metadata, array $metadataSelectValues, array $documentCustomMetadataValues) {
        $col = $this->addColumnText($metadata->title, $metadata->guiTitle);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($documentCustomMetadataValues, $metadataSelectValues, $metadata) {
            if(!isset($documentCustomMetadataValues[$row->documentId][$metadata->title])) {
                return '-';
            }
            $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];
            $tmp = $metadataSelectValues[$metadata->title][$tmp];

            $el = HTML::el('span')
                ->title($tmp)
                ->text($tmp);

            return $el;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($documentCustomMetadataValues, $metadataSelectValues, $metadata) {
            $tmp = $documentCustomMetadataValues[$row->documentId][$metadata->title];
            return $metadataSelectValues[$metadata->title][$tmp];
        };
    }

    /**
     * Returns all entity IDs that will be displayed in the grid
     * 
     * @return array Entity IDs
     */
    private function getEntityIdsInGrid() {
        $ds = clone $this->fetchDataFromDb();

        $ids = [];
        while($row = $ds->fetchAssoc()) {
            $ids[] = $row[$this->primaryKeyColName];
        }
        
        return $ids;
    }

    /**
     * Adds checkboxes to grid and forwards the selected IDs to the given handler (action in presenter)
     * 
     * @param APresenter $presenter Handler presenter
     * @param string $action Handler action
     */
    public function useCheckboxesWithCustomHandler(APresenter $presenter, string $action) {
        $this->addCheckboxes($presenter, $action);
    }

    /**
     * Adds checkboxes to grid and forward the selected IDs to "actionBulkAction()"
     * 
     * @param APresenter $presenter Sender presenter
     */
    public function useCheckboxes(APresenter $presenter) {
        $params = [];
        if($this->httpRequest->query('folderId') !== null) {
            $params['folderId'] = $this->httpRequest->query('folderId');
        }

        $this->addCheckboxes2($presenter, 'bulkAction', $params);
    }

    /**
     * Appends filters to the grid
     */
    private function appendFilters() {
        $this->addFilter(DocumentsGridSystemMetadata::STATUS, null, DocumentStatus::getAll());
        $this->addFilter(DocumentsGridSystemMetadata::AUTHOR_USER_ID, null, $this->getAuthorsInGrid());
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
            $authorId = $row[DocumentsGridSystemMetadata::AUTHOR_USER_ID];

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

    // HANDLERS
    /**
     * Handles bulk actions
     * 
     * @return JsonResponse Rendered modal content
     */
    public function actionBulkAction() {
        $modal = new BulkActionsModal($this);

        $ids = $this->httpRequest->query('ids');

        $this->documentBulkActionsHelper->setFolderId($this->getFolderId());
        $bulkActions = $this->documentBulkActionsHelper->getBulkActions($ids, $this->currentFolderId);

        $modal->setBulkActions($bulkActions);
        $modal->startup();

        return new JsonResponse(['modal' => $modal->render()]);
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

    public function actionQuickSearch() {
        $this->quickSearchQuery = $this->httpRequest->post('query');

        $this->prerender();

        return parent::actionQuickSearch();
    }
}

?>