<?php

namespace App\Modules\UserModule;

use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;

class DocumentBulkActionsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('DocumentBulkActionsPresenter', 'Document bulk actions');
    }

    public function handleStartProcess() {
        $process = $this->httpRequest->query('process');
        if($process === null) {
            throw new RequiredAttributeIsNotSetException('process');
        }
        $documentIds = $this->httpRequest->query['documentId'];

        $exceptions = [];

        try {
            $result = $this->processFactory->startDocumentProcess($process, $documentIds, $exceptions);

            if(!empty($exceptions)) {
                /**
                 * @var AException $exception
                 */
                foreach($exceptions as $exception) {
                    $this->flashMessage('Error during process: ' . $exception->getMessage(), 'error', 10);
                }
            }

            if($result === true && empty($exception)) {
                $this->flashMessage('Process run successfully.', 'success');
            } else {
                $this->flashMessage('An error occurred while running process.', 'error', 10);
            }
        } catch(AException $e) {
            $this->flashMessage('An error occurred while running process. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $backPage = $this->httpRequest->query('backPage');
        $backAction = $this->httpRequest->query('backAction');

        $backUrl = [];
        if($backPage !== null && $backAction !== null) {
            $backUrl = ['page' => $backPage, 'action' => $backAction];

            $folderId = $this->httpRequest->query('folderId');
            if($folderId !== null) {
                $backUrl['folderId'] = $folderId;
            }
        } else {
            $backUrl = $this->createFullURL('User:Documents', 'list');
            $folderId = $this->httpRequest->query('folderId');
            if($folderId !== null) {
                $backUrl['folderId'] = $folderId;
            }
        }

        $this->redirect($backUrl);
    }
}

?>