<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesSelect\ProcessesSelect;
use App\Components\ProcessViewSidebar\ProcessViewSidebar;
use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\StandaloneProcesses;
use App\Core\FileUploadManager;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;

class NewProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('NewProcessPresenter', 'New process');
    }

    public function renderSelect() {}

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = new ProcessViewSidebar($request, $this->supervisorAuthorizator, $this->getUserId());

        return $sidebar;
    }

    protected function createComponentProcessesSelect(HttpRequest $request) {
        $select = new ProcessesSelect($request, $this->standaloneProcessManager);
        $select->setApplication($this->app);
        $select->setPresenter($this);

        return $select;
    }

    public function handleStartProcess(?FormRequest $fr = null) {
        if($fr !== null) {
            $name = $this->httpRequest->get('name');
            if($name === null) {
                throw new RequiredAttributeIsNotSetException('name');
            }

            $methodName = 'start' . ucfirst($name);

            if(method_exists($this->standaloneProcessManager, $methodName)) {
                try {
                    $this->processRepository->beginTransaction(__METHOD__);

                    $_data = $fr->getData();

                    if(!empty($_FILES)) {
                        $fum = new FileUploadManager();
                        $data = $fum->uploadFile($_FILES['file'], null, $this->getUserId());

                        if(empty($data)) {
                            throw new GeneralException('Could not upload file.');
                        }

                        $fileId = $this->fileStorageManager->createNewFile(
                            null,
                            $this->getUserId(),
                            $data[FileUploadManager::FILE_FILENAME],
                            $data[FileUploadManager::FILE_FILEPATH],
                            $data[FileUploadManager::FILE_FILESIZE]
                        );

                        $_data['fileId'] = $fileId;
                    }

                    $this->standaloneProcessManager->$methodName($_data);

                    $this->processRepository->commit($this->getUserId(), __METHOD__);

                    $this->flashMessage('Process started.', 'success');
                } catch(AException $e) {
                    $this->processRepository->rollback(__METHOD__);

                    $this->flashMessage('Could not start process. Reason: ' . $e->getMessage(), 'error', 10);
                }
            } else {
                $this->flashMessage('Unknown process.', 'error', 10);
            }

            $this->redirect($this->createFullURL('User:Processes', 'list', ['view' => ProcessGridViews::VIEW_STARTED_BY_ME]));
        }
    }

    public function handleProcessForm() {
        $process = null;
        if($this->httpRequest->get('name') !== null) {
            $process = $this->httpRequest->get('name');
        }

        $name = StandaloneProcesses::toString($process);

        $this->saveToPresenterCache('processTitle', $name);

        $links = [
            $this->createBackUrl('select')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderProcessForm() {
        $this->template->process_title = $this->loadFromPresenterCache('processTitle');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentProcessForm(HttpRequest $request) {
        $process = null;
        if($request->get('name') !== null) {
            $process = $request->get('name');
        } else if($request->post('name') !== null) {
            $process = $request->post('name');
        }

        $form = $this->componentFactory->getStandaloneProcessFormByName($process, $this->standaloneProcessManager);

        if($form === null) {
            throw new GeneralException('No definition for process type "' . StandaloneProcesses::toString($process) . '" found in ComponentFactory.');
        }

        $form->baseUrl = ['page' => $request->get('page'), 'action' => 'startProcess'];
        $form->setComponentName('processForm');

        return $form;
    }
}

?>