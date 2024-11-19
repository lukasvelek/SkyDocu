<?php

namespace App\Components\DocumentsGrid;

use App\Authorizators\DocumentBulkActionAuthorizator;
use App\Authorizators\GroupStandardOperationsAuthorizator;
use App\Constants\Container\DocumentBulkActions;
use App\Core\Application;
use App\Core\Http\HttpRequest;
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

    private HttpRequest $request;

    public function __construct(Application $app, DocumentManager $dm, HttpRequest $request, DocumentBulkActionAuthorizator $dbaa, GroupStandardOperationsAuthorizator $gsoa) {
        $this->dm = $dm;
        $this->app = $app;
        $this->dbaa = $dbaa;
        $this->gsoa = $gsoa;

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

        if($this->dbaa->canExecuteArchivation($this->app->currentUser->getId(), $documentIds)) {
            $bulkActions[] = DocumentBulkActions::ARCHIVATION;
        }

        if($this->gsoa->canUserViewDocumentHistory($this->app->currentUser->getId())) {
            $bulkActions[] = DocumentBulkActions::DOCUMENT_HISTORY;
        }
        
        // 2. Create array of bulk action url
        $bulkActionsUrl = [];
        foreach($bulkActions as $bulkAction) {
            $bulkActionsUrl[] = $this->createBulkActionUrl($documentIds, $bulkAction);
        }

        // 3. Return the bulk action url array
        return $bulkActionsUrl;
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

        foreach($documentIds as $documentId) {
            $urlParams[] = 'documentId[]=' . $documentId;
        }

        $el = HTML::el('a')
                ->class('link')
                ->text(DocumentBulkActions::toString($bulkAction));

        switch($bulkAction) {
            case DocumentBulkActions::ARCHIVATION:
                $el->href($this->createLink('User:DocumentBulkActions', 'archiveDocuments', $urlParams));

                break;

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