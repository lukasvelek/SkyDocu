<?php

namespace App\Components\DocumentsGrid;

use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Constants\AppDesignThemes;
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
use App\Helpers\AppThemeHelper;
use App\Managers\Container\ArchiveManager;
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
    private GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    private EnumManager $enumManager;
    private GridManager $gridManager;
    private ArchiveManager $archiveManager;

    private bool $allMetadata;
    private ?string $currentFolderId;
    private bool $showDocumentInfoLink;
    private bool $showShared;
    private bool $isArchive;

    /**
     * Class constructor
     * 
     * @param GridBuilder $grid GridBuilder instance
     * @param Application $app Application instance
     * @param DocumentManager $documentManager DocumentManager instance
     * @param GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator
     * @param EnumManager $enumManager
     * @param GridManager $gridManager
     * @param ArchiveManager $archiveManager
     */
    public function __construct(
        GridBuilder $grid,
        Application $app,
        DocumentManager $documentManager,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        EnumManager $enumManager,
        GridManager $gridManager,
        ArchiveManager $archiveManager
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);

        $this->app = $app;
        $this->documentManager = $documentManager;
        $this->currentUserId = $app->currentUser->getId();
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->enumManager = $enumManager;
        $this->gridManager = $gridManager;
        $this->archiveManager = $archiveManager;

        $this->allMetadata = false;
        $this->currentFolderId = null;
        $this->showDocumentInfoLink = true;
        $this->showShared = false;
        $this->isArchive = false;
    }

    /**
     * Sets the current folder
     * 
     * @param string $folderId Current folder ID
     */
    public function setCurrentFolder(?string $folderId) {
        $this->currentFolderId = $folderId;
    }

    public function setCurrentArchiveFolder(?string $folderId) {
        $this->currentFolderId = $folderId;
        $this->isArchive = true;
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
        $this->setup();
        
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();

        if($this->allMetadata && !$this->showShared) {
            $this->appendCustomMetadata();
        }

        $this->appendActions();

        $this->appendFilters();

        parent::prerender();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        if(!$this->showShared) {
            $this->addQueryDependency('folderId', $this->getFolderId());
        }
        if($this->isArchive) {
            $this->addQueryDependency('isArchive', '1');
        }
        
        $this->addQuickSearch('title', 'Title');
    }

    /**
     * Appends grid actions
     */
    private function appendActions() {
        if($this->showDocumentInfoLink && !$this->isArchive) {
            $info = $this->addAction('info');
            $info->setTitle('Information');
            $info->onCanRender[] = function() {
                return true;
            };
            $info->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
                $el = HTML::el('a');
                $el->href($this->createFullURLString('User:Documents', 'info', ['documentId' => $primaryKey, 'folderId' => $row->folderId]))
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
            if(!$this->isArchive) {
                $qb = $this->documentManager->composeQueryForDocuments($this->currentUserId, $folderId, $this->allMetadata);
            } else {
                $documentIds = $this->archiveManager->getDocumentsForArchiveFolder($folderId);
                $qb = $this->documentManager->documentRepository->composeQueryForDocuments();
                $qb->andWhere($qb->getColumnInValues('documentId', $documentIds));
            }
        }

        // Implicitly order from newest to oldest
        $qb->orderBy('dateCreated', 'DESC');

        $this->createDataSourceFromQueryBuilder($qb, 'documentId');
    }

    /**
     * Returns current folder ID
     * 
     * @return string Current folder ID
     */
    private function getFolderId() {
        if($this->currentFolderId === null) {
            if($this->httpRequest->get('folderId') !== null) {
                $this->currentFolderId = $this->httpRequest->get('folderId');
            } else {
                throw new GeneralException('No folder is selected.');
            }
        }

        if($this->httpRequest->post('isArchive') !== null) {
            $this->isArchive = true;
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

        $dataSource = clone $this->filledDataSource;

        $documentIds = [];
        while($row = $dataSource->fetchAssoc()) {
            $documentIds[] = $row['documentId'];
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

                case DocumentsGridSystemMetadata::DATE_CREATED:
                    $this->addColumnDatetime(DocumentsGridSystemMetadata::DATE_CREATED, DocumentsGridSystemMetadata::toString(DocumentsGridSystemMetadata::DATE_CREATED));
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
        $ds = clone $this->fetchDataFromDb(true);

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
        if($this->httpRequest->get('folderId') !== null) {
            $params['folderId'] = $this->httpRequest->get('folderId');
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
    public function actionFilter(): JsonResponse {
        $this->getActiveFiltersFromCache();

        $this->prerender();

        return parent::actionFilter();
    }

    public function actionFilterClear(): JsonResponse {
        $this->prerender();

        return parent::actionFilterClear();
    }

    public function actionQuickSearch(): JsonResponse {
        $this->quickSearchQuery = $this->httpRequest->post('query');

        $this->prerender();

        return parent::actionQuickSearch();
    }

    public function actionGetSkeleton(): JsonResponse {
        $this->prerender();
        
        return parent::actionGetSkeleton();
    }

    public function actionBulkAction(): JsonResponse {
        $template = $this->getTemplate(__DIR__ . '\\test.html');

        if(AppThemeHelper::getAppThemeForUser($this->app) == AppDesignThemes::DARK) {
            $modalStyle = 'visibility: hidden; height: 0px; position: absolute; top: 5%; left: 5%; background-color: rgba(70, 70, 70, 1); z-index: 9999; border-radius: 5px;';
        } else {
            $modalStyle = 'visibility: hidden; height: 0px; position: absolute; top: 5%; left: 5%; background-color: rgba(225, 225, 225, 1); z-index: 9999; border-radius: 5px;';
        }
        
        $template->modal_style = $modalStyle;

        return new JsonResponse([
            'modal' => $template->render()->getRenderedContent()
        ]);
    }
}

?>