<?php

namespace App\Modules\UserModule;

use App\Components\ProcessWorkflow\ProcessWorkflow;
use App\Core\Http\HttpRequest;

class ProcessWorkflowPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ProcessWorkflowPresenter', 'Process workflow');
    }

    public function renderHistory() {
        $instanceId = $this->httpRequest->get('instanceId');
        $processId = $this->httpRequest->get('processId');
        $view = $this->httpRequest->get('view');

        $this->template->links = $this->createBackFullUrl('User:Processes', 'list', ['view' => $view]);
    }

    protected function createComponentProcessWorkflow(HttpRequest $request) {
        $pw = new ProcessWorkflow(
            $request,
            $this->app,
            $this->processInstanceManager,
            $this->processManager,
            $this->groupManager,
            $this->app->userManager
        );
        $pw->setInstanceId($request->get('instanceId'));
        $pw->setProcessId($request->get('processId'));

        return $pw;
    }
}

?>