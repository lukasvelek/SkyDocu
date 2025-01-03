<?php

namespace App\Modules\UserModule;

use App\Components\ProcessesSelect\ProcessesSelect;
use App\Components\ProcessForm\CommonProcessForm;
use App\Constants\Container\ProcessGridViews;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;

class NewProcessPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('NewProcessPresenter', 'New process');
    }

    public function renderSelect() {}

    protected function createComponentProcessViewsSidebar(HttpRequest $request) {
        $sidebar = $this->componentFactory->getSidebar();

        // START NEW PROCESS
        $sidebar->addLink('Start new process', $this->createFullURL('User:NewProcess', 'select'), true);

        $sidebar->addHorizontalLine();

        // VIEWS
        foreach(ProcessGridViews::getAll() as $name => $title) {
            $sidebar->addLink($title, $this->createFullURL('User:Processes', 'list', ['view' => $name]), false);
        }

        return $sidebar;
    }

    protected function createComponentProcessesSelect(HttpRequest $request) {
        $select = new ProcessesSelect($request, $this->standaloneProcessManager);

        return $select;
    }

    public function handleStartProcess(?FormRequest $fr = null) {
        if($fr !== null) {
            $name = $this->httpGet('name', true);

            $methodName = 'start' . ucfirst($name);

            if(method_exists($this->standaloneProcessManager, $methodName)) {
                try {
                    $this->processRepository->beginTransaction(__METHOD__);

                    $this->standaloneProcessManager->$methodName($fr->getData());

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
        $process = $this->httpGet('name');
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
        $processForm = new CommonProcessForm($request);
        $processForm->setProcess($request->query['name']);
        $processForm->setBaseUrl(['page' => $request->query['page'], 'action' => 'startProcess']);

        return $processForm;
    }
}

?>