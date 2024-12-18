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

/**
 * This class is a dedicated class for better maintaining document bulk actions
 * 
 * @author Lukas Velek
 */
class DocumentBulkActionsHelper {
    private DocumentManager $dm;
    private Application $app;
    private DocumentBulkActionAuthorizator $dbaa;
    private GroupStandardOperationsAuthorizator $gsoa;
    private ProcessFactory $pf;

    private HttpRequest $request;

    public function __construct(Application $app, DocumentManager $dm, HttpRequest $request, DocumentBulkActionAuthorizator $dbaa, GroupStandardOperationsAuthorizator $gsoa, ProcessFactory $pf) {
        $this->dm = $dm;
        $this->app = $app;
        $this->dbaa = $dbaa;
        $this->gsoa = $gsoa;
        $this->pf = $pf;

        $this->request = $request;
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

        /*if($this->dbaa->canExecuteArchivation($this->app->currentUser->getId(), $documentIds)) {
            $bulkActions[] = DocumentBulkActions::ARCHIVATION;
        }*/

        if($this->gsoa->canUserViewDocumentHistory($this->app->currentUser->getId())) {
            $bulkActions[] = DocumentBulkActions::DOCUMENT_HISTORY;
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
        $p = $this->pf->createDocumentArchivationProcess();
        if($p->canExecute($documentIds, null)) {
            $bulkActions[] = SystemProcessTypes::ARCHIVATION;
        }

        // Shredding request
        $p = $this->pf->createDocumentShreddingRequestProcess();
        if($p->canExecute($documentIds, null)) {
            $bulkActions[] = SystemProcessTypes::SHREDDING_REQUEST;
        }

        // Shredding
        $p = $this->pf->createDocumentShreddingProcess();
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
            'backPage=' . $this->request->query['page'],
            'backAction=' . $this->request->query['action'],
            'process=' . $bulkAction
        ];

        if(array_key_exists('folderId', $this->request->query)) {
            $urlParams[] = 'backFolderId=' . $this->request->query['folderId'];
        }

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
            'backPage=' . $this->request->query['page'],
            'backAction=' . $this->request->query['action']
        ];

        if(array_key_exists('folderId', $this->request->query)) {
            $urlParams['backFolderId'] = $this->request->query['folderId'];
        }

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
        $url = '?page=' . $modulePresenter . '&action=' . $action;

        if(!empty($params)) {
            $url .= '&' . implode('&', $params);
        }

        return $url;
    }
}

?>