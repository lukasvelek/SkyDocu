<?php

namespace App\Components\DocumentsGrid;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Constants\Container\DocumentBulkActions;
use App\Constants\Container\SystemProcessTypes;
use App\Core\Application;
use App\Core\Http\HttpRequest;
use App\Lib\Processes\ProcessFactory;
use App\Managers\Container\DocumentManager;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

/**
 * This class is a dedicated class for better maintaining document bulk actions
 * 
 * @author Lukas Velek
 */
class DocumentBulkActionsHelper {
    private DocumentManager $documentManager;
    private Application $app;
    private DocumentBulkActionAuthorizator $documentBulkActionAuthorizator;
    private GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator;
    private ProcessFactory $processFactory;
    private string $folderId;
    private bool $isArchive;

    private HttpRequest $request;

    public function __construct(
        Application $app,
        DocumentManager $documentManager,
        HttpRequest $request,
        DocumentBulkActionAuthorizator $documentBulkActionAuthorizator,
        GroupStandardOperationsAuthorizator $groupStandardOperationsAuthorizator,
        ProcessFactory $processFactory
    ) {
        $this->documentManager = $documentManager;
        $this->app = $app;
        $this->documentBulkActionAuthorizator = $documentBulkActionAuthorizator;
        $this->groupStandardOperationsAuthorizator = $groupStandardOperationsAuthorizator;
        $this->processFactory = $processFactory;

        $this->request = $request;

        $this->isArchive = false;
    }

    /**
     * Sets the current folder ID
     * 
     * @param string $folderId
     */
    public function setFolderId(string $folderId) {
        $this->folderId = $folderId;
    }

    /**
     * Sets the current archive folder ID
     * 
     * @param string $folderId
     */
    public function setArchiveFolderId(string $folderId) {
        $this->setFolderId($folderId);
        $this->isArchive = true;
    }

    /**
     * Returns all available bulk actions for given array of document IDs
     * 
     * @param array<string> $documentIds Document IDs
     * @return array<string> HTML code of bulk actions
     */
    public function getBulkActions(array $documentIds) {
        // 1. Create array of allowed bulk actions
        $bulkActions = [];
        $processBulkActions = [];

        /*if($this->gsoa->canUserViewDocumentHistory($this->app->currentUser->getId())) {
            $bulkActions[] = DocumentBulkActions::DOCUMENT_HISTORY;
        }*/

        if($this->groupStandardOperationsAuthorizator->canUserShareDocuments($this->app->currentUser->getID()) && $this->checkIfDocumentsCanBeShared($documentIds)) {
            $bulkActions[] = DocumentBulkActions::SHARING;
        }

        // 1b. Create array of allowed processes
        $this->appendProcessBulkActions($documentIds, $processBulkActions);
        
        // 2. Create array of bulk action url
        $bulkActionsUrl = [];
        foreach($bulkActions as $bulkAction) {
            $bulkActionsUrl[] = $this->createBulkActionUrl($documentIds, $bulkAction);
        }
        foreach($processBulkActions as $processBulkAction) {
            $bulkActionsUrl[] = $this->createBulkActionUrlForProcess($documentIds, $processBulkAction);
        }

        // 3. Return the bulk action url array
        return $bulkActionsUrl;
    }

    /**
     * Returns all available process bulk actions for given array of document IDs
     * 
     * @param array<string> $documentIds Document IDs
     * @param array<string> Bulk actions
     */
    private function appendProcessBulkActions(array $documentIds, array &$bulkActions) {
        // Archivation
        $archivation = true;
        foreach($documentIds as $id) {
            if(!$this->documentBulkActionAuthorizator->canExecuteArchivation($this->app->currentUser->getId(), $id)) {
                $archivation = false;
            }
        }

        if($archivation) {
            $bulkActions[] = SystemProcessTypes::ARCHIVATION;
        }

        // Move to archive
        $moveToArchive = true;
        foreach($documentIds as $id) {
            if(!$this->documentBulkActionAuthorizator->canExecuteMoveToArchive($this->app->currentUser->getId(), $id)) {
                $moveToArchive = false;
            }
        }

        if($moveToArchive) {
            $bulkActions[] = SystemProcessTypes::MOVE_TO_ARCHIVE;
        }

        // Move from archive
        $moveFromArchive = true;
        foreach($documentIds as $id) {
            if(!$this->documentBulkActionAuthorizator->canExecuteMoveFromArchive($this->app->currentUser->getId(), $id)) {
                $moveFromArchive = false;
            }
        }

        if($moveFromArchive) {
            $bulkActions[] = SystemProcessTypes::MOVE_FROM_ARCHIVE;
        }

        // Shredding request
        $p = $this->processFactory->createDocumentShreddingRequestProcess();
        if($p->canExecute($documentIds, null)) {
            $bulkActions[] = SystemProcessTypes::SHREDDING_REQUEST;
        }

        // Shredding
        $p = $this->processFactory->createDocumentShreddingProcess();
        if($p->canExecute($documentIds, null)) {
            $bulkActions[] = SystemProcessTypes::SHREDDING;
        }
    }

    /**
     * Creates single line process bulk action handler URL
     * 
     * @param array $documentIds Selected document IDs
     * @param string $bulkAction Bulk action
     */
    private function createBulkActionUrlForProcess(array $documentIds, string $bulkAction) {
        $urlParams = [
            'backPage=' . $this->request->query('page'),
            'backAction=' . $this->request->query('action'),
            'process=' . $bulkAction
        ];

        $urlParams[] = 'backFolderId=' . $this->folderId;

        foreach($documentIds as $documentId) {
            $urlParams[] = 'documentId[]=' . $documentId;
        }

        $el = HTML::el('a')
                ->class('link')
                ->text(SystemProcessTypes::toString($bulkAction))
                ->href($this->createLink('User:DocumentBulkActions', 'startProcess', $urlParams));;

        return $el->toString();
    }

    /**
     * Creates single line bulk action handler URL
     * 
     * @param array $documentIds Selected document IDs
     * @param string $bulkAction Bulk action
     */
    private function createBulkActionUrl(array $documentIds, string $bulkAction) {
        $urlParams = [
            'backPage=' . $this->request->query('page'),
            'backAction=' . $this->request->query('action')
        ];

        $urlParams[] = 'backFolderId=' . $this->folderId;

        foreach($documentIds as $documentId) {
            $urlParams[] = 'documentId[]=' . $documentId;
        }

        $el = HTML::el('a')
                ->class('link')
                ->text(DocumentBulkActions::toString($bulkAction));

        switch($bulkAction) {
            case DocumentBulkActions::DOCUMENT_HISTORY:
                $el->href($this->createLink('User:Documents', 'documentHistory', $urlParams));
                break;

            case DocumentBulkActions::SHARING:
                $el->href($this->createLink('User:Documents', 'shareForm', $urlParams));
                break;
        }

        return $el->toString();
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
        return LinkBuilder::convertUrlArrayToString(array_merge(['page' => $modulePresenter, 'action' => $action], $params));
    }

    /**
     * Checks if given documents can be shared
     * 
     * @param array $documentIds Document IDs
     * @return bool True or false
     */
    private function checkIfDocumentsCanBeShared(array $documentIds) {
        // document must not be in archive
        if($this->isArchive) {
            return false;
        }

        // document must not be shared to current user
        $sharedDocuments = $this->documentManager->getSharedDocumentsForUser($this->app->currentUser->getId(), false);

        foreach($sharedDocuments as $sharedDocumentId) {
            if(in_array($sharedDocumentId, $documentIds)) {
                return false;
            }
        }

        return true;
    }
}

?>