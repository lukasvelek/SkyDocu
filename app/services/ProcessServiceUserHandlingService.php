<?php

namespace App\Services;

use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceSystemStatus;
use App\Core\Application;
use App\Core\Container;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Helpers\ProcessEditorHelper;
use Exception;

class ProcessServiceUserHandlingService extends AService {
    private string $containerId;
    private string $instanceId;

    public function __construct(Application $app) {
        parent::__construct('ProcessServiceUserHandling', $app);
    }

    public function run() {
        global $argv;
        $_argv = $argv;
        unset($_argv[0]);

        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop(null, $_argv);
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
            $this->serviceStop($e, $_argv);
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        global $argv;

        if(count($argv) == 1) {
            throw new ServiceException('No arguments passed');
        }

        $this->containerId = $argv[1];
        $this->instanceId = $argv[2];

        $container = new Container($this->app, $this->containerId);

        $this->handleOperations($container);
    }

    private function handleOperations(Container $container) {
        $this->logInfo('Started processing instance ID \'' . $this->instanceId . '\'.');

        try {
            $instance = $container->processInstanceManager->getProcessInstanceById($this->instanceId);

            $container->processInstanceManager->updateInstance($this->instanceId, [
                'systemStatus' => ProcessInstanceSystemStatus::IN_PROGRESS
            ]);

            $processId = $instance->processId;
            $process = $container->processManager->getProcessById($processId);

            $instanceData = unserialize($instance->data);

            $definition = json_decode(base64_decode($process->definition), true);
            $forms = $definition['forms'];

            $workflowIndex = $instanceData['workflowIndex'];

            $form = json_decode($forms[$workflowIndex]['form'], true);

            $operations = ProcessEditorHelper::getServiceUserDefinitionUpdateOperations($form);

            if(array_key_exists('status', $operations)) {
                $container->processInstanceManager->changeProcessInstanceStatus($this->instanceId, $operations['status']);

                $this->logInfo(sprintf('Changed instance status %d => %d', $instance->status, $operations['status']));
            }
            if(array_key_exists('instanceDescription', $operations)) {
                $container->processInstanceManager->changeProcessInstanceDescription($this->instanceId, $operations['instanceDescription']);
                
                $this->logInfo(sprintf('Changed instance description \'%s\' => \'%s\'.', $instance->description, $operations['instanceDescription']));
            }

            $workflow = [];
            foreach($forms as $_form) {
                $workflow[] = $_form['actor'];
            }

            [$officer, $officerType] = $container->processInstanceManager->evaluateNextProcessInstanceOfficer($instance, $workflow, $this->app->userManager->getServiceUserId(), $workflowIndex + 1);

            $container->processInstanceManager->moveProcessInstanceToNextOfficer($this->instanceId, $this->app->userManager->getServiceUserId(), $officer, $officerType);

            $instance = $container->processInstanceManager->getProcessInstanceById($this->instanceId);

            $instanceData = unserialize($instance->data);

            $instanceData['workflowHistory'][][$this->app->userManager->getServiceUserId()] = [
                'operation' => ProcessInstanceOperations::PROCESS,
                'date' => date('Y-m-d H:i:s')
            ];

            $container->processInstanceManager->updateInstance($this->instanceId, [
                'data' => serialize($instanceData),
                'systemStatus' => ProcessInstanceSystemStatus::FINISHED
            ]);

            $this->logInfo('Finished processing instance ID \'' . $this->instanceId . '\'.');
        } catch(AException $e) {
            $this->logError('An error occured during processing instance ID \'' . $this->instanceId . '\'. Reason:' . $e->getMessage());
        }
    }
}

?>