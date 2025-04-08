<?php

namespace App\Modules\UserModule;

use App\Constants\Container\DocumentBulkActions;
use App\Constants\Container\DocumentStatus;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;

class DocumentBulkActionsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('DocumentBulkActionsPresenter', 'Document bulk actions');
    }

    public function handleStartProcess() {
        $process = $this->httpRequest->get('process');
        if($process === null) {
            throw new RequiredAttributeIsNotSetException('process');
        }
        $documentIds = $this->httpRequest->get('documentId');

        $exceptions = [];

        switch($process) {
            case DocumentBulkActions::MOVE_TO_ARCHIVE:
                $backPage = $this->httpRequest->get('backPage');
                $backAction = $this->httpRequest->get('backAction');
                $folderId = $this->httpRequest->get('backFolderId');

                $url = $this->createURLString('moveToArchiveForm');

                $urlParts = [];
                if($backPage !== null && $backAction !== null) {
                    $urlParts[] = 'backPage=' . $backPage;
                    $urlParts[] = 'backAction=' . $backAction;
                }
                if($folderId !== null)  {
                    $urlParts[] = 'folderId=' . $folderId;
                }
                
                foreach($documentIds as $documentId) {
                    $urlParts[] = 'documentIds[]=' . $documentId;
                }

                $url .= '&' . implode('&', $urlParts);

                $this->redirect($url);
                break;

            case DocumentBulkActions::MOVE_FROM_ARCHIVE:
                $backPage = $this->httpRequest->get('backPage');
                $backAction = $this->httpRequest->get('backAction');
                $folderId = $this->httpRequest->get('backFolderId');

                $url = $this->createURLString('moveFromArchive');

                $urlParts = [];
                if($backPage !== null && $backAction !== null) {
                    $urlParts[] = 'backPage=' . $backPage;
                    $urlParts[] = 'backAction=' . $backAction;
                }
                if($folderId !== null)  {
                    $urlParts[] = 'folderId=' . $folderId;
                }
                
                foreach($documentIds as $documentId) {
                    $urlParts[] = 'documentIds[]=' . $documentId;
                }

                $url .= '&' . implode('&', $urlParts);

                $this->redirect($url);
                break;

            default:
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
                break;
        }

        $backPage = $this->httpRequest->get('backPage');
        $backAction = $this->httpRequest->get('backAction');

        $backUrl = [];
        if($backPage !== null && $backAction !== null) {
            $backUrl = ['page' => $backPage, 'action' => $backAction];

            $folderId = $this->httpRequest->get('folderId');
            if($folderId !== null) {
                $backUrl['folderId'] = $folderId;
            }
        } else {
            $backUrl = $this->createFullURL('User:Documents', 'list');
            $folderId = $this->httpRequest->get('folderId');
            if($folderId !== null) {
                $backUrl['folderId'] = $folderId;
            }
        }

        $this->redirect($backUrl);
    }

    public function handleMoveToArchiveForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->archiveRepository->beginTransaction(__METHOD__);

                $documentIds = explode(';', $fr->documentIds);

                foreach($documentIds as $documentId) {
                    $this->documentManager->updateDocument($documentId, ['status' => DocumentStatus::IS_BEING_MOVED_TO_ARCHIVE]);
                    $this->archiveManager->insertDocumentToArchiveFolder($documentId, $fr->archiveFolder);
                }

                $this->archiveRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Documents moved to archive.', 'success');
            } catch(AException $e) {
                $this->archiveRepository->rollback(__METHOD__);

                $this->flashMessage('Could not move documents to archive. Reason: ' . $e->getMessage(), 'error', 10);
            }

            if($this->httpRequest->get('backPage') !== null && $this->httpRequest->get('backAction') !== null) {
                $params['page'] = $this->httpRequest->get('backPage');
                $params['action'] = $this->httpRequest->get('backAction');
            }
            if($this->httpRequest->get('folderId') !== null) {
                $params['folderId'] = $this->httpRequest->get('folderId');
            }
            $this->redirect($params);
        }
    }

    public function renderMoveToArchiveForm() {
        $params = [];
        if($this->httpRequest->get('backPage') !== null && $this->httpRequest->get('backAction') !== null) {
            $params['page'] = $this->httpRequest->get('backPage');
            $params['action'] = $this->httpRequest->get('backAction');
        }
        if($this->httpRequest->get('folderId') !== null) {
            $params['folderId'] = $this->httpRequest->get('folderId');
        }

        $this->template->links = $this->createBackFullUrl($params['page'], $params['action'], ['folderId' => $params['folderId']]);
    }

    protected function createComponentArchiveDocumentForm(HttpRequest $request) {
        $archiveFoldersDb = $this->archiveManager->getAvailableArchiveFolders();

        $archiveFolders = [];
        foreach($archiveFoldersDb as $archiveFolder) {
            $archiveFolders[] = [
                'value' => $archiveFolder->folderId,
                'text' => $archiveFolder->title
            ];
        }

        $params = [];
        if($request->get('backPage') !== null && $request->get('backAction') !== null) {
            $params['backPage'] = $request->get('backPage');
            $params['backAction'] = $request->get('backAction');
        }
        if($request->get('folderId') !== null) {
            $params['folderId'] = $request->get('folderId');
        }
        $documentIds = $request->get('documentIds') ?? [];

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('moveToArchiveForm', $params));

        $form->addTextInput('documentIds')
            ->setValue(implode(';', $documentIds))
            ->setHidden();

        $form->addSelect('archiveFolder', 'Archive folder:')
            ->setRequired()
            ->addRawOptions($archiveFolders);

        $form->addSubmit('Archive');

        return $form;
    }

    public function handleMoveFromArchive() {
        try {
            $this->archiveRepository->beginTransaction(__METHOD__);

            $documentIds = $this->httpRequest->get('documentIds') ?? [];

            foreach($documentIds as $documentId) {
                $this->documentManager->updateDocument($documentId, ['status' => DocumentStatus::NEW]);
                $this->archiveManager->removeDocumentFromArchiveFolder($documentId);
            }

            $this->archiveRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Documents archived.', 'success');
        } catch(AException $e) {
            $this->archiveRepository->rollback(__METHOD__);

            $this->flashMessage('Could not archive documents. Reason: ' . $e->getMessage(), 'error', 10);
        }

        if($this->httpRequest->get('backPage') !== null && $this->httpRequest->get('backAction') !== null) {
            $params['page'] = $this->httpRequest->get('backPage');
            $params['action'] = $this->httpRequest->get('backAction');
        }
        if($this->httpRequest->get('folderId') !== null) {
            $params['folderId'] = $this->httpRequest->get('folderId');
        }
        $this->redirect($params);
    }
}

?>