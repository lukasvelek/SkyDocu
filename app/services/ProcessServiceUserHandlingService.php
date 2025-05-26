<?php

namespace App\Services;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceStatus;
use App\Core\DatabaseConnection;
use App\Core\DB\DatabaseManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Helpers\ProcessEditorHelper;
use App\Logger\Logger;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\Container\ProcessManager;
use App\Managers\ContainerManager;
use App\Managers\EntityManager;
use App\Managers\UserManager;
use App\Repositories\Container\GroupRepository;
use App\Repositories\Container\ProcessInstanceRepository;
use App\Repositories\Container\ProcessRepository;
use App\Repositories\ContentRepository;
use App\Repositories\TransactionLogRepository;
use App\Repositories\UserRepository;
use Exception;

class ProcessServiceUserHandlingService extends AService {
    private string $containerId;
    private string $instanceId;

    private ContainerManager $containerManager;
    private UserManager $userManager;
    private DatabaseManager $dbManager;
    private UserRepository $userRepository;

    public function __construct(Logger $logger, ServiceManager $serviceManager, ContainerManager $containerManager, UserManager $userManager, DatabaseManager $dbManager, UserRepository $userRepository) {
        parent::__construct('ProcessServiceUserHandling', $logger, $serviceManager);

        $this->containerManager = $containerManager;
        $this->userManager = $userManager;
        $this->dbManager = $dbManager;
        $this->userRepository = $userRepository;
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

        $container = $this->containerManager->getContainerById($this->containerId, true);

        $conn = $this->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $transactionLogRepository = new TransactionLogRepository($conn, $this->logger);
        $contentRepository = new ContentRepository($conn, $this->logger, $transactionLogRepository);
        $entityManager = new EntityManager($this->logger, $contentRepository);
        $processInstanceRepository = new ProcessInstanceRepository($conn, $this->logger, $transactionLogRepository);
        $groupRepository = new GroupRepository($conn, $this->logger, $transactionLogRepository);
        $groupManager = new GroupManager($this->logger, $entityManager, $groupRepository, $this->userRepository);
        $instanceManager = new ProcessInstanceManager($this->logger, $entityManager, $processInstanceRepository, $groupManager, $this->userManager);
        $processRepository = new ProcessRepository($conn, $this->logger, $transactionLogRepository);
        $processManager = new ProcessManager($this->logger, $entityManager, $processRepository);

        $this->handleOperations($instanceManager, $processManager);
    }

    private function handleOperations(ProcessInstanceManager $instanceManager, ProcessManager $processManager) {
        $this->logInfo('Started processing instance ID \'' . $this->instanceId . '\'.');

        try {
            $instance = $instanceManager->getProcessInstanceById($this->instanceId);

            $processId = $instance->processId;
            $process = $processManager->getProcessById($processId);

            $instanceData = unserialize($instance->data);

            $definition = json_decode(base64_decode($process->definition), true);
            $forms = $definition['forms'];

            $workflowIndex = $instanceData['workflowIndex'];

            $form = json_decode($forms[$workflowIndex]['form'], true);

            $operations = ProcessEditorHelper::getServiceUserDefinitionUpdateOperations($form);

            if(array_key_exists('status', $operations)) {
                $instanceManager->changeProcessInstanceStatus($this->instanceId, $operations['status']);

                $this->logInfo(sprintf('Changed instance status %d => %d', $instance->status, $operations['status']));
            }
            if(array_key_exists('instanceDescription', $operations)) {
                $instanceManager->changeProcessInstanceDescription($this->instanceId, $operations['instanceDescription']);
                
                $this->logInfo(sprintf('Changed instance description \'%s\' => \'%s\'.', $instance->description, $operations['instanceDescription']));
            }

            $workflow = [];
            foreach($forms as $_form) {
                $workflow[] = $_form['actor'];
            }

            [$officer, $officerType] = $instanceManager->evaluateNextProcessInstanceOfficer($workflow, $this->userManager->getServiceUserId(), $workflowIndex + 1);

            $instanceManager->moveProcessInstanceToNextOfficer($this->instanceId, $officer, $officerType);

            $instance = $instanceManager->getProcessInstanceById($this->instanceId);

            $instanceData = unserialize($instance->data);

            $instanceData['workflowHistory'][][$this->userManager->getServiceUserId()] = [
                'operation' => ProcessInstanceOperations::PROCESS,
                'date' => date('Y-m-d H:i:s')
            ];

            $instanceManager->updateInstance($this->instanceId, [
                'data' => serialize($instanceData)
            ]);

            $this->logInfo('Finished processing instance ID \'' . $this->instanceId . '\'.');
        } catch(AException $e) {
            $this->logError('An error occured during processing instance ID \'' . $this->instanceId . '\'. Reason:' . $e->getMessage());
        }
    }
}

?>