<?php

namespace App\Components\ProcessWorkflow;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceOperations;
use App\Constants\Container\ProcessInstanceStatus;
use App\Constants\Container\SystemGroups;
use App\Core\Application;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Helpers\ColorHelper;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessInstanceManager;
use App\Managers\Container\ProcessManager;
use App\Managers\UserManager;
use App\UI\AComponent;

/**
 * ProcessWorkflow displays the workflow of process instance
 * 
 * @author Lukas Velek
 */
class ProcessWorkflow extends AComponent {
    private ProcessInstanceManager $processInstanceManager;
    private ProcessManager $processManager;
    private GroupManager $groupManager;
    private UserManager $userManager;

    private ?string $instanceId = null;
    private ?string $processId = null;

    private array $mColors = [];

    public function __construct(
        HttpRequest $request,
        Application $app,
        ProcessInstanceManager $processInstanceManager,
        ProcessManager $processManager,
        GroupManager $groupManager,
        UserManager $userManager
    ) {
        parent::__construct($request);

        $this->setApplication($app);

        $this->processInstanceManager = $processInstanceManager;
        $this->processManager = $processManager;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    /**
     * Sets process instance ID
     * 
     * @param string $instanceId Process instance ID
     */
    public function setInstanceId(string $instanceId) {
        $this->instanceId = $instanceId;
    }

    /**
     * Sets process ID
     * 
     * @param string $processId Process ID
     */
    public function setProcessId(string $processId) {
        $this->processId = $processId;
    }

    public function startup() {
        parent::startup();

        $this->setup();
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $steps = $this->createWorkflowSteps();

        $template->workflow_steps = implode('<br>', $steps);

        return $template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Sets up the component
     */
    private function setup() {
        if($this->instanceId === null) {
            throw new GeneralException('No process instance is set.');
        }
        if($this->processId === null) {
            $instance = $this->processInstanceManager->getProcessInstanceById($this->instanceId);
            $this->processId = $instance->processId;
        }
    }

    /**
     * Returns process workflow
     */
    private function getProcessWorkflow(): array {
        $process = $this->processManager->getProcessById($this->processId);

        $workflow = unserialize($process->workflow);

        return $workflow;
    }

    /**
     * Returns process instance workflow
     */
    private function getInstanceWorkflow(): array {
        $instance = $this->processInstanceManager->getProcessInstanceById($this->instanceId);

        $data = unserialize($instance->data);

        $workflowHistory = [];
        if(array_key_exists('workflowHistory', $data)) {
            foreach($data['workflowHistory'] as $wh) {
                foreach($wh as $actor => $response) {
                    $workflowHistory[] = [
                        'actor' => $actor,
                        'actorType' => null,
                        'response' => $response
                    ];
                }
            }
        }

        if($instance->status == ProcessInstanceStatus::IN_PROGRESS) {
            $workflowHistory[] = [
                'actor' => $instance->currentOfficerId,
                'actorType' => $instance->currentOfficerType,
                'response' => null
            ];
        }

        return $workflowHistory;
    }

    /**
     * Returns color for actor
     * 
     * @param string $actor Actor
     */
    private function getColor(string $actor) {
        if(!array_key_exists($actor, $this->mColors)) {
            [$fg, $bg] = ColorHelper::createColorCombination();

            $this->mColors[$actor] = [$bg, $fg];
        }

        return $this->mColors[$actor];
    }

    /**
     * Creates workflow steps and returns an array of rendered workflow step templates
     */
    private function createWorkflowSteps(): array {
        $results = [];

        $workflow = $this->getProcessWorkflow();
        $workflowHistory = $this->getInstanceWorkflow();

        $i = 0;
        foreach($workflow as $w) {
            $template = $this->getTemplate(__DIR__ . '\\workflow-step-template.html');

            $index = $i + 1;
            
            if(array_key_exists($i, $workflowHistory)) {
                // workflow history entry exists
                $actor = $workflowHistory[$i]['actor'];
                $response = $workflowHistory[$i]['response'];
                $type = $workflowHistory[$i]['actorType'];

                if($type !== null) {
                    // is current officer and has a type
                    switch($type) {
                        case ProcessInstanceOfficerTypes::GROUP:
                            $group = $this->groupManager->getGroupById($actor);
                            $actor = SystemGroups::toString($group->title);

                            break;

                        case ProcessInstanceOfficerTypes::USER:
                            $user = $this->userManager->getUserById($actor);
                            $actor = $user->getFullname();

                            break;
                    }
                } else {
                    // is a history officer -> is automatically a user
                    $user = $this->userManager->getUserById($actor);
                    $actor = $user->getFullname();
                }

                if($response !== null) { // response exists
                    switch($response) {
                        case ProcessInstanceOperations::ACCEPT:
                            $response = 'Accepted';
                            break;
                    
                        case ProcessInstanceOperations::ARCHIVE:
                            $response = 'Archived';
                            break;
            
                        case ProcessInstanceOperations::CANCEL:
                            $response = 'Canceled';
                            break;
            
                        case ProcessInstanceOperations::FINISH:
                            $response = 'Finished';
                            break;
            
                        case ProcessInstanceOperations::REJECT:
                            $response = 'Rejected';
                            break;
                    }

                    $response = ucfirst($response);

                    $text = sprintf('#%d %s [<i>%s</i>] - <b>%s</b>', $index, $actor, $w, $response);
                } else {
                    $text = sprintf('#%d %s [<i>%s</i>]', $index, $actor, $w);
                }
            } else {
                $text = sprintf('#%d ? [<i>%s</i>]', $index, $w);
            }

            // fill template
            [$bg, $fg] = $this->getColor($w);
            
            $template->bg_color = $bg;
            $template->fg_color = $fg;
            $template->workflow_actor = $text;
            $template->workflow_step_index = $i;

            $results[] = $template->render()->getRenderedContent();

            $i++;
        }

        return $results;
    }
}

?>