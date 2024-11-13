<?php

namespace App\Components\DocumentsGrid;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Constants\Container\CustomMetadataTypes;
use App\Constants\Container\DocumentStatus;
use App\Core\Application;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Helpers\GridHelper;
use App\Managers\Container\DocumentManager;
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
    private DocumentManager $dm;
    private DocumentBulkActionAuthorizator $dbaa;

    private bool $allMetadata;

    private ?string $currentFolderId;

    /**
     * Class constructor
     * 
     * @param GridBuilder $grid GridBuilder instance
     * @param Application $app Application instance
     * @param DocumentManager $documentManager DocumentManager instance
     */
    public function __construct(
        GridBuilder $grid,
        Application $app,
        DocumentManager $documentManager,
        DocumentBulkActionAuthorizator $dbaa
    ) {
        parent::__construct($grid->httpRequest);
        $this->setHelper(new GridHelper($app->logger, $app->currentUser->getId()));

        $this->app = $app;
        $this->dm = $documentManager;
        $this->currentUserId = $app->currentUser->getId();
        $this->dbaa = $dbaa;

        $this->allMetadata = false;

        $this->currentFolderId = null;
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
     * Adds system metadata columns and if any custom metadata exist then it also adds custom metadata columns. It also adds values of the custom metadata. Finally it renders the grid.
     * 
     * @return string HTML code
     */
    public function render() {
        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();

        if($this->allMetadata) {
            $this->appendCustomMetadata();
        }

        $this->setup();

        return parent::render();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->addQueryDependency('folderId', $this->getFolderId());
    }

    /**
     * Creates data source for the grid
     */
    public function createDataSource() {
        $folderId = $this->getFolderId();

        $qb = $this->dm->composeQueryForDocuments($this->currentUserId, $folderId, $this->allMetadata);

        $this->createDataSourceFromQueryBuilder($qb, 'documentId');
    }

    /**
     * Returns current folder ID
     * 
     * @return string Current folder ID
     */
    private function getFolderId() {
        if($this->currentFolderId === null) {
            if(isset($this->httpRequest->query['folderId'])) {
                $this->currentFolderId = $this->httpRequest->query['folderId'];
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
        $this->addColumnText('title', 'Title');
        $this->addColumnUser('authorUserId', 'Author');
        $this->addColumnConst('status', 'Status', DocumentStatus::class);
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
        $customMetadatas = $this->dm->getCustomMetadataForFolder($this->getFolderId());

        $metadataSelectValues = [];
        $documentCustomMetadataValues = [];
        foreach($customMetadatas as $metadataId => $metadata) {
            if($metadata->type == CustomMetadataTypes::ENUM) {
                $metadataSelectValues[$metadata->title] = $this->dm->getMetadataValues($metadataId);
            }

            $qb = $this->dm->composeQueryForDocumentCustomMetadataValues();
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
            }
        }
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
        $this->addCheckboxes2($presenter, 'bulkAction');
    }

    // HANDLERS
    /**
     * Handles bulk actions
     * 
     * @return array<string, string> Rendered modal content
     */
    public function actionBulkAction() {
        $modal = new BulkActionsModal($this);

        $ids = $this->httpRequest->query['ids'];

        $bulkActions = $this->getAllowedBulkActions($ids);
        $bulkActions = $this->createBulkActions($bulkActions, $ids);

        $modal->setBulkActions($bulkActions);
        $modal->startup();

        return ['modal' => $modal->render()];
    }

    /**
     * Gets allowed bulk actions
     * 
     * @param array $documentIds Selected document IDs
     * @return array<string> Bulk action list
     */
    private function getAllowedBulkActions(array $documentIds) {
        $bulkActions = [];

        if($this->dbaa->canExecuteArchivation($this->presenter->getUserId(), $documentIds)) {
            $bulkActions[] = 'archivation';
        }

        return $bulkActions;
    }

    /**
     * Creates bulk action links
     * 
     * @param array $bulkActions Allowed bulk actions
     * @param array $documentIds Selected document IDs
     * @return array<string> Bulk actions HTML code
     */
    private function createBulkActions(array $bulkActions, array $documentIds) {
        $urlParams = [
            'backPage=' . $this->httpRequest->query['page'],
            'backAction=' . $this->httpRequest->query['action']
        ];

        foreach($documentIds as $documentId) {
            $urlParams[] = 'documentId[]=' . $documentId;
        }

        $links = [];
        foreach($bulkActions as $ba) {
            $el = HTML::el('a')
                ->class('link');

            switch($ba) {
                case 'archivation':
                    $el->href($this->createLink('User:DocumentBulkActions', 'archiveDocuments', $urlParams))
                        ->text('Archive');
                    break;
            }

            $links[] = $el->toString();
        }
        return $links;
    }

    /**
     * Creates link from given parameters
     * 
     * @param string $modulePresenter Module name and presenter name
     * @param string $action Action name
     * @param array $params Parameters
     * @return string URL
     */
    private function createLink(string $modulePresenter, string $action, array $params = []) {
        $url = '?page=' . $modulePresenter . '&action=' . $action;

        if(!empty($params)) {
            $url .= '&' . implode('&', $params);
        }

        return $url;
    }
}

?>