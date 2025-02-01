<?php

namespace App\Modules\UserModule;

use App\Constants\Container\DocumentBulkActions;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
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
        $documentIds = $this->httpRequest->query('documentId');

        $exceptions = [];

        if($process == DocumentBulkActions::ARCHIVATION) {
            $backPage = $this->httpRequest->query('backPage');
            $backAction = $this->httpRequest->query('backAction');
            $folderId = $this->httpRequest->query('backFolderId');

            $url = $this->createURLString('archiveForm');

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
        } else {
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

    public function handleArchiveForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                // remove document from document folder
                // move document to archive folder (create relation)

                $this->archiveRepository->beginTransaction(__METHOD__);

                $documentIds = explode(';', $fr->documentIds);

                foreach($documentIds as $documentId) {
                    $this->documentManager->removeDocumentFromFolder($documentId);
                    $this->archiveManager->insertDocumentToArchiveFolder($documentId, $fr->archiveFolder);
                }

                $this->archiveRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Documents archived.', 'success');
            } catch(AException $e) {
                $this->archiveRepository->rollback(__METHOD__);

                $this->flashMessage('Could not archive documents. Reason: ' . $e->getMessage(), 'error', 10);
            }

            if($this->httpRequest->query('backPage') !== null && $this->httpRequest->query('backAction') !== null) {
                $params['page'] = $this->httpRequest->query('backPage');
                $params['action'] = $this->httpRequest->query('backAction');
            }
            if($this->httpRequest->query('folderId') !== null) {
                $params['folderId'] = $this->httpRequest->query('folderId');
            }
            $this->redirect($params);
        } else {
            $params = [];
            if($this->httpRequest->query('backPage') !== null && $this->httpRequest->query('backAction') !== null) {
                $params['page'] = $this->httpRequest->query('backPage');
                $params['action'] = $this->httpRequest->query('backAction');
            }
            if($this->httpRequest->query('folderId') !== null) {
                $params['folderId'] = $this->httpRequest->query('folderId');
            }
            $this->saveToPresenterCache('link', $this->createBackFullUrl($params['page'], $params['action'], ['folderId' => $params['folderId']]));
        }
    }

    public function renderArchiveForm() {
        $this->template->links = $this->loadFromPresenterCache('link');
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
        if($request->query('backPage') !== null && $request->query('backAction') !== null) {
            $params['backPage'] = $request->query('backPage');
            $params['backAction'] = $request->query('backAction');
        }
        if($request->query('folderId') !== null) {
            $params['folderId'] = $request->query('folderId');
        }
        $documentIds = $request->query('documentIds') ?? [];

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('archiveForm', $params));

        $form->addTextInput('documentIds')
            ->setValue(implode(';', $documentIds))
            ->setHidden();

        $form->addSelect('archiveFolder', 'Archive folder:')
            ->setRequired()
            ->addRawOptions($archiveFolders);

        $form->addSubmit('Archive');

        return $form;
    }
}

?>