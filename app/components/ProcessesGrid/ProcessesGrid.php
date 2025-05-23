<?php

namespace App\Components\ProcessesGrid;

use App\Authorizators\ContainerProcessAuthorizator;
use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Constants\Container\ProcessInstanceStatus;
use App\Constants\Container\SystemGroups;
use App\Constants\ProcessColorCombos;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessManager;
use App\Repositories\Container\ProcessInstanceRepository;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\IGridExtendingComponent;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

/**
 * ProcessesGrid is used for displaying process instances in a grid/list view
 * 
 * @author Lukas Velek
 */
class ProcessesGrid extends GridBuilder implements IGridExtendingComponent {
    private ProcessInstanceRepository $processInstanceRepository;
    private GroupManager $groupManager;
    private ProcessManager $processManager;
    private ContainerProcessAuthorizator $processAuthorizator;
    
    private string $view;

    public function __construct(
        GridBuilder $grid,
        ProcessInstanceRepository $processInstanceRepository,
        string $view,
        GroupManager $groupManager,
        ProcessManager $processManager,
        ContainerProcessAuthorizator $processAuthorizator
    ) {
        parent::__construct($grid->httpRequest);

        $this->setHelper($grid->getHelper());
        $this->setCacheFactory($grid->cacheFactory);
        $this->setApplication($grid->app);

        $this->processInstanceRepository = $processInstanceRepository;
        $this->view = $view;
        $this->groupManager = $groupManager;
        $this->processManager = $processManager;
        $this->processAuthorizator = $processAuthorizator;

        $this->gridName = 'ProcessesGrid_' . $this->view;
    }

    public function createDataSource() {
        $dsHelper = new ProcessesGridDatasourceHelper(
            $this->view,
            $this->processInstanceRepository,
            $this->app->currentUser->getId(),
            $this->groupManager
        );

        $this->createDataSourceFromQueryBuilder($dsHelper->composeQb(), 'instanceId');
    }

    public function prerender() {
        $this->setup();

        $this->createDataSource();

        $this->fetchDataFromDb();

        $this->appendSystemMetadata();
        $this->appendActions();

        parent::prerender();
    }

    /**
     * Sets up the grid
     */
    private function setup() {
        $this->addQueryDependency('view', $this->view);
    }

    /**
     * Appends system metadata columns to grid
     */
    private function appendSystemMetadata() {
        $col = $this->addColumnText('processTitle', 'Process');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $process = $this->processManager->getProcessById($row->processId);

            $el = HTML::el('span');
            $el->text($process->title)
                ->style('color', ProcessColorCombos::getColor($process->colorCombo))
                ->style('background-color', ProcessColorCombos::getBackgroundColor($process->colorCombo))
                ->style('padding', '5px')
                ->style('border-radius', '12px');

            return $el;
        };

        $this->addColumnText('description', 'Description');

        $col = $this->addColumnText('currentOfficer', 'Officer');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            if($row->currentOfficerType == ProcessInstanceOfficerTypes::GROUP) {
                $group = $this->groupManager->getGroupById($row->currentOfficerId);

                $el->text(SystemGroups::toString($group->title));
            } else if($row->currentOfficerType == ProcessInstanceOfficerTypes::USER) {
                $user = $this->app->userManager->getUserById($row->currentOfficerId);

                $el->text($user->getFullname());
            } else {
                return null;
            }

            return $el;
        };

        $this->addColumnConst('status', 'Status', ProcessInstanceStatus::class);
    }

    /**
     * Appends actions to grid
     */
    private function appendActions() {
        $userGroups = $this->groupManager->getGroupsForUser($this->app->currentUser->getId());
        $adminGroup = $this->groupManager->getGroupByTitle(SystemGroups::ADMINISTRATORS)->groupId;
        $processSupervisorGroup = $this->groupManager->getGroupByTitle(SystemGroups::PROCESS_SUPERVISOR)->groupId;

        $canSee = (in_array($adminGroup, $userGroups) || in_array($processSupervisorGroup, $userGroups));

        // OPEN PROCESS INSTANCE
        $open = $this->addAction('open');
        $open->setTitle('Open');
        $open->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) use ($canSee) {
            if($canSee) {
                return true;
            }

            return $this->evaluateOpenActionVisibilityForOfficer($row);
        };
        $open->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('User:Process', 'processForm', ['instanceId' => $primaryKey, 'processId' => $row->processId, 'view' => $this->view]))
                ->text('Open')
            ;

            return $el;
        };

        // PROCESS INSTANCE WORKFLOW HISTORY
        $workflowHistory = $this->addAction('workflowHistory');
        $workflowHistory->setTitle('Workflow history');
        $workflowHistory->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $this->processAuthorizator->canUserViewProcessInstanceWorkflowHistory($row->instanceId, $this->app->currentUser->getId());
        };
        $workflowHistory->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->class('grid-link')
                ->href($this->createFullURLString('User:ProcessWorkflow', 'history', ['instanceId' => $primaryKey, 'processId' => $row->processId, 'view' => $this->view]))
                ->text('Workflow history')
            ;

            return $el;
        };
    }

    /**
     * Evaluates if grid action "Open" is visible for current user
     * 
     * @param DatabaseRow $row DatabaseRow instance
     */
    private function evaluateOpenActionVisibilityForOfficer(DatabaseRow $row): bool {
        $userGroups = $this->groupManager->getGroupsForUser($this->app->currentUser->getId());

        $type = $row->currentOfficerType;
        $officer = $row->currentOfficerId;

        if($type == ProcessInstanceOfficerTypes::GROUP) {
            $group = $this->groupManager->getGroupById($officer);

            if(in_array($group->groupId, $userGroups)) {
                return true;
            }
        } else if($type == ProcessInstanceOfficerTypes::USER) {
            $user = $this->app->userManager->getUserById($officer);

            if($user->getId() == $this->app->currentUser->getId()) {
                return true;
            }
        }

        return false;
    }

    public function actionGetSkeleton(): JsonResponse {
        $this->prerender();

        return parent::actionGetSkeleton();
    }
}

?>