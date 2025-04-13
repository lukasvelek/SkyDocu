<?php

namespace App\Modules\SuperAdminModule;

use App\Components\ContainerUsageAverageResponseTimeGraph\ContainerUsageAverageResponseTimeGraph;
use App\Components\ContainerUsageStatsGraph\ContainerUsageStatsGraph;
use App\Components\ContainerUsageTotalResponseTimeGraph\ContainerUsageTotalResponseTimeGraph;
use App\Components\Widgets\FileStorageStatsForContainerWidget\FileStorageStatsForContainerWidget;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\ContainerEnvironments;
use App\Constants\ContainerInviteUsageStatus;
use App\Constants\ContainerStatus;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseMigrationManager;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\LinkHelper;
use App\Managers\Container\FileStorageManager;
use App\Managers\EntityManager;
use App\Repositories\Container\FileStorageRepository;
use App\Repositories\Container\ProcessRepository;
use App\Repositories\ContentRepository;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ContainerSettingsPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ContainerSettingsPresenter', 'Container settings');
    }

    public function renderHome() {}

    protected function createComponentContainerInfoForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->get('containerId'));

        try {
            $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');
        } catch(AException $e) {
            $groupUsers = [];
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->addTextInput('containerId', 'Container ID:')
            ->setDisabled()
            ->setValue($container->getId());

        $form->addTextInput('containerTitle', 'Container title:')
            ->setDisabled()
            ->setValue($container->getTitle());

        $form->addNumberInput('containerUserCount', 'Container users:')
            ->setDisabled()
            ->setValue(count($groupUsers));

        $user = $this->app->userManager->getUserById($container->getUserId());

        $form->addTextInput('containerReferent', 'Container referent:')
            ->setDisabled()
            ->setValue($user->getFullname());

        $dateCreated = new DateTime(strtotime($container->getDateCreated()));

        $form->addDateTimeInput('containerDateCreated', 'Date created:')
            ->setDisabled()
            ->setValue($dateCreated);

        $form->addTextInput('containerEnvironment', 'Container environment:')
            ->setDisabled()
            ->setValue(ContainerEnvironments::toString($container->getEnvironment()));

        $form->addTextInput('containerDbSchema', 'Database schema:')
            ->setDisabled()
            ->setValue($container->getDefaultDatabase()->getDbSchema());

        $form->addTextInput('containerIsInDistribution', 'Is container in distribution:')
            ->setDisabled()
            ->setValue($container->isInDistribution() ? 'Yes' : 'No');

        return $form;
    }
    
    protected function createComponentContainerPendingInvitesGrid(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->get('containerId'));

        $grid = $this->componentFactory->getGridBuilder($container->getId());

        $qb = $this->app->containerInviteManager->composeQueryForContainerInviteUsages($container->getId());

        $qb->andWhere('status = ?', [ContainerInviteUsageStatus::NEW])
            ->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');
        $grid->addQueryDependency('containerId', $container->getId());
        $grid->setLimit(5);

        $col = $grid->addColumnText('userUsername', 'Username');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $data = unserialize($row->data);

            return $data['username'];
        };

        $col = $grid->addColumnText('userFullname', 'Fullname');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $data = unserialize($row->data);

            return $data['fullname'];
        };

        return $grid;
    }

    public function handleStatus(?FormRequest $fr = null) {
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                $this->app->containerManager->changeContainerStatus($containerId, $fr->status, $this->getUserId(), $fr->description);
                
                /**
                 * @var \App\Modules\SuperAdminModule\SuperAdminModule $module
                 */
                $module = &$this->module;
                $module->navbar?->revalidateContainerSwitch();

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Container status changed.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);

                $this->flashMessage('Could not change container status. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('status', ['containerId' => $containerId]));
        }
    }

    public function renderStatus() {
        $containerId = $this->httpRequest->get('containerId');

        $links = [];

        $container = $this->app->containerManager->getContainerById($containerId);

        if($container->getStatus() != ContainerStatus::REQUESTED) {
            $links[] = LinkBuilder::createSimpleLink('Show history', $this->createURL('listStatusHistory', ['containerId' => $this->httpRequest->get('containerId')]), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentContainerStatusForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->get('containerId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('status', ['containerId' => $request->get('containerId')]));

        $disabled = false;
        $statuses = [];
        foreach(ContainerStatus::getAll() as $key => $value) {
            if(in_array($container->getStatus(), [ContainerStatus::NEW, ContainerStatus::IS_BEING_CREATED, ContainerStatus::ERROR_DURING_CREATION, ContainerStatus::REQUESTED])) {
                $status = [
                    'text' => $value,
                    'value' => $key
                ];

                if($container->getStatus() == $key) {
                    $status['selected'] = 'selected';
                }

                $statuses[] = $status;
                $disabled = true;
            } else {
                if(in_array($key, [ContainerStatus::IS_BEING_CREATED, ContainerStatus::NEW, ContainerStatus::ERROR_DURING_CREATION, ContainerStatus::REQUESTED])){
                    continue;
                }

                $status = [
                    'text' => $value,
                    'value' => $key
                ];

                if($container->getStatus() == $key) {
                    $status['selected'] = 'selected';
                }

                $statuses[] = $status;
            }
        }

        $form->addSelect('status', 'Status:')
            ->addRawOptions($statuses)
            ->setDisabled($disabled);

        $form->addTextArea('description', 'Description:')
            ->setRequired()
            ->setDisabled($disabled);

        $submit = $form->addSubmit('Save')
            ->setDisabled($disabled);

        if($disabled) {
            $submit->addAttribute('title', 'Status cannot be changed currently.');
        }

        return $form;
    }

    protected function createComponentContainerPermanentFlashMessageForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->get('containerId'));
        $disabled = false;

        if($container->getStatus() == ContainerStatus::REQUESTED) {
            $disabled = true;
        }

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('statusPermanentFlashMessage', ['containerId' => $request->get('containerId')]));

        $permanentFlashMessage = $form->addTextArea('permanentFlashMessage', 'Flash message text:')
            ->setRequired()
            ->setDisabled($disabled);

        $permanentFlashMessage->setContent($container->getPermanentFlashMessage());

        $form->addSubmit('Save')
            ->setDisabled($disabled);
        $form->addButton('Clear')
            ->setOnClick('location.href = \'' . $this->createURLString('statusClearPermanentFlashMessage', ['containerId' => $container->getId()]) . '\';')
            ->setDisabled($disabled);

        return $form;
    }

    public function handleStatusPermanentFlashMessage(?FormRequest $fr = null) {
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }
        
        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                $data['permanentFlashMessage'] = $fr->permanentFlashMessage;

                $this->app->containerManager->updateContainer($containerId, $data);

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Permanent flash message successfully saved.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);

                $this->flashMessage('Could not update permanent flash message.', 'error', 10);
            }

            $this->redirect($this->createURL('status', ['containerId' => $containerId]));
        }
    }

    public function handleStatusClearPermanentFlashMessage() {
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        try {
            $this->app->containerRepository->beginTransaction(__METHOD__);

            $data['permanentFlashMessage'] = null;

            $this->app->containerManager->updateContainer($containerId, $data);

            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Permanent flash message successfully cleared.', 'success');
        } catch(AException $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->flashMessage('Could not clear permanent flash message.', 'error', 10);
        }

        $this->redirect($this->createURL('status', ['containerId' => $containerId]));
    }

    public function renderListStatusHistory() {
        $this->template->links = [
            $this->createBackUrl('status', ['containerId' => $this->httpRequest->get('containerId')])
        ];
    }

    protected function createComponentContainerStatusHistoryGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->containerRepository->composeQueryForContainerStatusHistory($request->get('containerId')), 'historyId');
        $grid->addQueryDependency('containerId', $request->get('containerId'));

        $grid->addColumnUser('userId', 'User');
        $col = $grid->addColumnText('description', 'Description');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if($row->userId != $this->app->userManager->getServiceUserId()) {
                return '<span style="color: grey">User comment: </span>' . $value;
            } else {
                return $value;
            }
        };
        $grid->addColumnConst('oldStatus', 'Old status', ContainerStatus::class);
        $grid->addColumnConst('newStatus', 'New status', ContainerStatus::class);
        $grid->addColumnDatetime('dateCreated', 'Date');

        return $grid;
    }

    public function renderAdvanced() {
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        $containerDeleteLink = HTML::el('a')
            ->class('link')
            ->href($this->createURLString('containerDeleteForm', ['containerId' => $containerId]))
            ->style('color', 'red')
            ->text('Delete')
            ->title('Delete')
            ->toString()
        ;

        $this->template->container_delete_link = $containerDeleteLink;

        $removeFromDistribLink = HTML::el('a')
            ->class('link')
            ->href($this->createURLString('containerRemoveFromDistributionForm', ['containerId' => $containerId]))
            ->style('color', 'red')
            ->text('Remove from distribution')
            ->title('Remove from distribution')
            ->toString();

        $this->template->container_remove_from_distribution_link = $removeFromDistribLink;
    }

    public function handleContainerDeleteForm(?FormRequest $fr = null) {
        $containerId = $this->httpRequest->get('containerId');

        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                $container = $this->app->containerManager->getContainerById($containerId);

                if($fr->title != $container->getTitle()) {
                    throw new GeneralException('Entered container title does not match with the container title.');
                }

                try {
                    $this->app->userAuth->authUser($fr->password);
                } catch(AException $e) {
                    throw new GeneralException('Incorrect password entered.');
                }

                $this->app->containerManager->deleteContainer($containerId);

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Container deleted.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);

                $this->flashMessage('Could not delete container. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createFullURL('SuperAdmin:Containers', 'list'));
        }
    }

    public function renderContainerDeleteForm() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('advanced', ['containerId' => $this->httpRequest->get('containerId')]), 'link')
        ];
    }

    protected function createComponentContainerDeleteForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->get('containerId'));

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('containerDeleteForm', ['containerId' => $request->get('containerId')]));

        $form->addTextInput('title', 'Container title (\'' . $container->getTitle() . '\'):')
            ->setRequired();

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addSubmit('Delete');

        return $form;
    }

    public function handleContainerRemoveFromDistributionForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $_hash = md5($fr->hash);

                if($_hash !== $this->httpRequest->get('h')) {
                    throw new GeneralException('Entered verification code does not match the code generated by the system.');
                }
                
                $this->app->userAuth->authUser($fr->password);

                $this->app->containerRepository->beginTransaction(__METHOD__);

                $this->app->containerManager->updateContainer($this->httpRequest->get('containerId'), [
                    'isInDistribution' => 0
                ]);

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Container successfully removed from distribution.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);

                $this->flashMessage('Could not remove container from distribution. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('advanced', ['containerId' => $this->httpRequest->get('containerId')]));
        }
    }

    public function renderContainerRemoveFromDistributionForm() {}

    protected function createComponentContainerRemoveFromDistributionForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();
        
        $form->addLabel('lbl_text1', 'This action will permanently remove the container from distribution. That means that its database schema will not be updated automatically during SkyDocu update.');

        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();

        $hash = null;

        $form->addSecurityVerificationCodeCheck('hash', $hash);

        $form->addSubmit('Remove');

        $form->setAction($this->createURL('containerRemoveFromDistributionForm', ['containerId' => $request->get('containerId'), 'h' => md5($hash)]));

        return $form;
    }

    public function renderUsageStatistics() {
        $this->template->links = LinkBuilder::createSimpleLink('Clear statistics', $this->createURL('clearUsageStatistics', ['containerId' => $this->httpRequest->get('containerId')]), 'link');
    }

    protected function createComponentContainerUsageStatsGraph(HttpRequest $request) {
        $graph = new ContainerUsageStatsGraph($request, $this->app->containerRepository);

        $graph->setContainerId($request->get('containerId'));
        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentContainerUsageAverageResponseTimeGraph(HttpRequest $request) {
        $graph = new ContainerUsageAverageResponseTimeGraph($request, $this->app->containerRepository);

        $graph->setContainerId($request->get('containerId'));
        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentContainerUsageTotalResponseTimeGraph(HttpRequest $request) {
        $graph = new ContainerUsageTotalResponseTimeGraph($request, $this->app->containerRepository);

        $graph->setContainerId($request->get('containerId'));
        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentFileStorageStatsWidget(HttpRequest $request) {
        $containerId = $request->get('containerId');
        $container = $this->app->containerManager->getContainerById($containerId);
        $containerConnection = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $contentRepository = new ContentRepository($containerConnection, $this->logger);
        $fileStorageRepository = new FileStorageRepository($containerConnection, $this->logger);

        $entityManager = new EntityManager($this->logger, $contentRepository);
        $fileStorageManager = new FileStorageManager($this->logger, $entityManager, $fileStorageRepository);

        $widget = new FileStorageStatsForContainerWidget($request, $fileStorageManager);
        $widget->addQueryDependency('containerId', $containerId);

        return $widget;
    }

    public function handleClearUsageStatistics(?FormRequest $fr = null) {
        $containerId = $this->httpRequest->get('containerId');

        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);
    
                $this->app->userAuth->authUser($fr->password);

                $deleteAll = false;
                if($fr->isset('deleteAll')) {
                    $deleteAll = true;
                }

                $this->app->containerManager->deleteContainerUsageStatistics($containerId, 5, $deleteAll);
    
                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);
    
                $this->flashMessage('Usage statistics cleared. Please run the background service in order to display statistics.', 'success');
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);
    
                $this->flashMessage('Could not clear usage statistics. Reason: ' . $e->getMessage(), 'error');
            }
    
            $this->redirect($this->createURL('usageStatistics', ['containerId' => $containerId]));
        }
    }

    public function renderClearUsageStatistics() {}

    protected function createComponentClearUsageStatisticsConfirmationForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('clearUsageStatistics', ['containerId' => $request->get('containerId')]));
        
        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();

        if($this->app->containerManager->getContainerUsageStatisticsTotalCount($request->get('containerId')) > 5) {
            // more than currently displayed in graphs

            $form->addLabel('lbl_moreContainersFound', 'More containers than is currently displayed in graphs found. Please choose whether you want to delete the <b>last 5 entries</b> or <b>all entries</b>.');

            $form->addCheckboxInput('deleteAll', 'Delete all?');
        }

        $form->addSubmit('Clear');

        return $form;
    }

    public function renderInvites() {
        $this->addScript('
            async function copyToClipboard(_link, _text) {
                var copyText = $("#" + _link).html();

                copyText = copyText.replaceAll("&amp;", "&");

                navigator.clipboard.writeText(copyText);

                $("#" + _text).html("Copied to clipboard!");

                await sleep(1000);

                $("#" + _text).html("Copy to clipboard");
            }
        ');

        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        try {
            $invite = $this->app->containerInviteManager->getInviteForContainer($containerId);
        } catch(AException $e) {
            $this->redirect($this->createURL('invitesWithoutGrid', ['containerId' => $containerId]));
        }

        $inviteLink = 'http://' . APP_URL . $this->createFullURLString('Anonym:RegistrationInvite', 'form', ['inviteId' => $invite->inviteId]);

        $inviteLink = HTML::el('span')
            ->text($inviteLink)
            ->addAtribute('hidden', null)
            ->id('inviteLinkUrl');

        $copyToClipboardLink = HTML::el('a')
            ->onClick('copyToClipboard(\'inviteLinkUrl\', \'inviteLinkText\')')
            ->text('Copy to clipboard')
            ->href('#')
            ->class('link')
            ->id('inviteLinkText');

        $links = [
            'Invite link: ' . $inviteLink->toString() . $copyToClipboardLink->toString(),
            'Invite link valid until: ' . DateTimeFormatHelper::formatDateToUserFriendly($invite->dateValid),
            LinkBuilder::createSimpleLink('Regenerate invite link', $this->createURL('generateInviteLink', ['containerId' => $containerId, 'regenerate' => '1', 'oldInviteId' => $invite->inviteId]), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentContainerInvitesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->containerInviteManager->composeQueryForContainerInviteUsages($request->get('containerId'));
        $qb->orderBy('status');
        
        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');
        $grid->addQueryDependency('containerId', $request->get('containerId'));

        $grid->addColumnConst('status', 'Status', ContainerInviteUsageStatus::class);

        $col = $grid->addColumnText('username', 'Username');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $data = unserialize($row->data);

            return $data['username'];
        };

        $col = $grid->addColumnText('fullname', 'Full name');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $data = unserialize($row->data);

            return $data['fullname'];
        };
        
        $grid->addColumnDatetime('dateCreated', 'Date');

        $accept = $grid->addAction('accept');
        $accept->setTitle('Accept');
        $accept->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status == ContainerInviteUsageStatus::NEW;
        };
        $accept->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('acceptInvite', ['entryId' => $primaryKey, 'containerId' => $row->containerId]))
                ->text('Accept')
                ->class('grid-link')
                ->style('color', 'green');

            return $el;
        };

        $reject = $grid->addAction('reject');
        $reject->setTitle('Reject');
        $reject->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status == ContainerInviteUsageStatus::NEW;
        };
        $reject->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('rejectInvite', ['entryId' => $primaryKey, 'containerId' => $row->containerId]))
                ->text('Reject')
                ->class('grid-link')
                ->style('color', 'red');

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->status <> ContainerInviteUsageStatus::NEW;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('deleteInvite', ['entryId' => $primaryKey, 'containerId' => $row->containerId]))
                ->text('Delete')
                ->class('grid-link');

            return $el;
        };

        return $grid;
    }

    public function renderInvitesWithoutGrid() {
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        $inviteLink = LinkBuilder::createSimpleLink('Generate invite link', $this->createURL('generateInviteLink', ['containerId' => $containerId]), 'link');

        $this->template->links = $inviteLink;
    }

    public function handleGenerateInviteLink() {
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }
        $regenerate = $this->httpRequest->get('regenerate') !== null;

        $dateValid = new DateTime();
        $dateValid->modify('+1m');
        $dateValid = $dateValid->getResult();

        try {
            $this->app->containerInviteRepository->beginTransaction(__METHOD__);

            if($regenerate) {
                $inviteId = $this->httpRequest->get('oldInviteId');

                $this->app->containerInviteManager->disableContainerInvite($inviteId);
            }

            $this->app->containerInviteManager->createContainerInvite($containerId, $dateValid);

            $this->app->containerInviteRepository->commit($this->getUserId(), __METHOD__);

            if($regenerate) {
                $this->flashMessage('Container\'s invite link has been regenerated.', 'success');
            } else {
                $this->flashMessage('Container\'s invite link has been generated.', 'success');
            }
            $this->redirect($this->createURL('invites', ['containerId' => $containerId]));
        } catch(AException $e) {
            $this->app->containerInviteRepository->rollback(__METHOD__);

            if($regenerate) {
                $this->flashMessage('Could not regenerate invite link. Reason: ' . $e->getMessage(), 'error', 10);
            } else {
                $this->flashMessage('Could not generate invite link. Reason: ' . $e->getMessage(), 'error', 10);
            }
            $this->redirect($this->createURL('home', ['containerId' => $containerId]));
        }
    }

    public function handleAcceptInvite() {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        try {
            $entry = $this->app->containerInviteManager->getInviteUsageById($entryId);

            $this->app->containerInviteRepository->beginTransaction(__METHOD__);

            $data = [
                'status' => ContainerInviteUsageStatus::ACCEPTED
            ];

            $this->app->containerInviteManager->updateContainerInviteUsage($entryId, $data);

            $tmp = unserialize($entry->data);

            $this->app->userManager->createNewUser($tmp['username'], $tmp['fullname'], $tmp['password'], (array_key_exists('email', $tmp) ? $tmp['email'] : null));

            $this->app->containerInviteRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Account request for invited user has been accepted and their account has been created. The request can now be deleted.', 'success');
        } catch(AException $e) {
            $this->app->containerInviteRepository->rollback(__METHOD__);
            
            $this->flashMessage('Could not accept account request. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('invites', ['containerId' => $containerId]));
    }

    public function handleRejectInvite() {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        try {
            $this->app->containerInviteRepository->beginTransaction(__METHOD__);

            $data = [
                'status' => ContainerInviteUsageStatus::REJECTED
            ];

            $this->app->containerInviteManager->updateContainerInviteUsage($entryId, $data);

            $this->app->containerInviteRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Account request for invited user has been rejected. The request can now be deleted.', 'success');
        } catch(AException $e) {
            $this->app->containerInviteRepository->rollback(__METHOD__);
            
            $this->flashMessage('Could not reject account request. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('invites', ['containerId' => $containerId]));
    }

    public function handleDeleteInvite() {
        $entryId = $this->httpRequest->get('entryId');
        if($entryId === null) {
            throw new RequiredAttributeIsNotSetException('entryId');
        }
        $containerId = $this->httpRequest->get('containerId');
        if($containerId === null) {
            throw new RequiredAttributeIsNotSetException('containerId');
        }

        try {
            $this->app->containerInviteRepository->beginTransaction(__METHOD__);

            $this->app->containerInviteManager->deleteContainerInviteUsage($entryId);

            $this->app->containerInviteRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Account request for invited user has been deleted.', 'success');
        } catch(AException $e) {
            $this->app->containerInviteRepository->rollback(__METHOD__);
            
            $this->flashMessage('Could not delete account request. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('invites', ['containerId' => $containerId]));
    }

    public function renderTransactionLog() {}

    protected function createComponentContainerTransactionLogGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($request->get('containerId'));

        $qb = $this->app->transactionLogRepository->composeQueryForTransactionLog($request->get('containerId'));

        $grid->createDataSourceFromQueryBuilder($qb, 'transactionId');
        $grid->addQueryDependency('containerId', $request->get('containerId'));

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnText('callingMethod', 'Method');
        $grid->addColumnDatetime('dateCreated', 'Date');

        return $grid;
    }

    public function renderProcesses() {
        $this->template->links = LinkBuilder::createSimpleLink('Add process', $this->createURL('addProcessForm', ['containerId' => $this->httpRequest->get('containerId')]), 'link');
    }

    protected function createComponentContainerProcessesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($request->get('containerId'));

        $container = $this->app->containerManager->getContainerById($request->get('containerId'));
        $containerConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $processRepository = new ProcessRepository($containerConn, $this->logger);
        $qb = $processRepository->composeQueryForProcessTypes();

        $grid->createDataSourceFromQueryBuilder($qb, 'typeId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnBoolean('isEnabled', 'Enabled');

        $enable = $grid->addAction('enable');
        $enable->setTitle('Enable');
        $enable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->isEnabled == false;
        };
        $enable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a');
            $el->text('Enable')
                ->class('grid-link')
                ->href($this->createURLString('modifyContainerProcess', ['containerId' => $request->get('containerId'), 'type' => $row->typeKey, 'param' => 'enable']));

            return $el;
        };

        $disable = $grid->addAction('disable');
        $disable->setTitle('Disable');
        $disable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return $row->isEnabled == true;
        };
        $disable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a');
            $el->text('Disable')
                ->class('grid-link')
                ->href($this->createURLString('modifyContainerProcess', ['containerId' => $request->get('containerId'), 'type' => $row->typeKey, 'param' => 'disable']));

            return $el;
        };

        return $grid;
    }

    public function handleModifyContainerProcess() {
        $action = $this->httpRequest->get('param');
        $type = $this->httpRequest->get('type');

        $data = [];
        if($action == 'enable') {
            $data['isEnabled'] = 1;
        } else {
            $data['isEnabled'] = 0;
        }

        $container = $this->app->containerManager->getContainerById($this->httpRequest->get('containerId'));
        $containerConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $processRepository = new ProcessRepository($containerConn, $this->logger);
        
        try {
            $processRepository->beginTransaction(__METHOD__);

            if(!$processRepository->updateProcessType($type, $data)) {
                throw new GeneralException('Database error.');
            }

            $processRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Process ' . ($action == 'enable' ? 'enabled' : 'disabled') . '.', 'success');
        } catch(AException $e) {
            $processRepository->rollback(__METHOD__);

            $this->flashMessage('Process could not be ' . ($action == 'enable' ? 'enabled' : 'disabled') . '. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('processes', ['containerId' => $this->httpRequest->get('containerId')]));
    }

    public function handleAddProcessForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $container = $this->app->containerManager->getContainerById($this->httpRequest->get('containerId'));
            $containerConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

            $processRepository = new ProcessRepository($containerConn, $this->logger);
            $contentRepository = new ContentRepository($containerConn, $this->logger);
            $entityManager = new EntityManager($this->logger, $contentRepository);

            try {
                $processRepository->beginTransaction(__METHOD__);
                
                $typeId = $entityManager->generateEntityId(EntityManager::C_PROCESS_TYPES);

                $result = $processRepository->insertNewProcessType(
                    $typeId,
                    $fr->process,
                    StandaloneProcesses::toString($fr->process),
                    StandaloneProcesses::getDescription($fr->process)
                );

                if($result === false) {
                    throw new GeneralException('Database error.');
                }

                $this->cacheFactory->invalidateCacheByNamespace(CacheNames::PROCESS_TYPES);

                $processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Process added to container.', 'success');
            } catch(AException $e) {
                $processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not add new process. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('processes', ['containerId' => $this->httpRequest->get('containerId')]));
        }
    }

    public function renderAddProcessForm() {
        $this->template->links = $this->createBackUrl('processes', ['containerId' => $this->httpRequest->get('containerId')]);
    }

    protected function createComponentContainerAddProcessForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('addProcessForm', ['containerId' => $request->get('containerId')]));

        $container = $this->app->containerManager->getContainerById($request->get('containerId'));
        $containerConn = $this->app->dbManager->getConnectionToDatabase($container->getDefaultDatabase()->getName());

        $processRepository = new ProcessRepository($containerConn, $this->logger);
        $qb = $processRepository->composeQueryForProcessTypes()
            ->execute();

        $processesDb = [];
        while($row = $qb->fetchAssoc()) {
            $processesDb[] = $row['typeKey'];
        }

        $processes = StandaloneProcesses::getAll();
        $processSelect = [];
        foreach($processes as $key => $title) {
            if(in_array($key, $processesDb)) continue;
            $processSelect[] = [
                'value' => $key,
                'text' => $title
            ];
        }

        $empty = false;
        if(empty($processSelect)) {
            $empty = true;
            $processSelect[] = [
                'value' => 'none',
                'text' => 'No processes found'
            ];
        }

        $form->addSelect('process', 'Process:')
            ->addRawOptions($processSelect)
            ->setRequired()
            ->setDisabled($empty);

        $form->addSubmit('Add')
            ->setDisabled($empty);

        return $form;
    }

    public function renderListDatabases() {

    }

    protected function createComponentContainerDatabasesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($request->get('containerId'));

        $container = $this->app->containerManager->getContainerById($request->get('containerId'));

        $qb = $this->app->containerDatabaseRepository->composeQueryForContainerDatabases();
        $qb->andWhere('containerId = ?', [$request->get('containerId')]);

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');
        $grid->addQueryDependency('containerId', $container->getId());

        $grid->addColumnText('name', 'Database name');
        $grid->addColumnBoolean('isDefault', 'System database');
        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');
        $grid->addColumnText('dbSchema', 'Schema');

        $runMigrations = $grid->addAction('runMigrations');
        $runMigrations->setTitle('Run migrations');
        $runMigrations->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) use ($container) {
            if(!$container->isInDistribution()) {
                return false;
            }

            if($row->isDefault == false) {
                return false;
            }

            if(in_array($container->getStatus(), [
                ContainerStatus::ERROR_DURING_CREATION,
                ContainerStatus::IS_BEING_CREATED,
                ContainerStatus::NEW,
                ContainerStatus::REQUESTED,
                ContainerStatus::RUNNING
            ])) {
                return false;
            }

            return true;
        };
        $runMigrations->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($request) {
            $el = HTML::el('a');
            $el->text('Run migrations')
                ->class('grid-link')
                ->href($this->createURLString('runDatabaseMigrations', ['containerId' => $request->get('containerId'), 'entryId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleRunDatabaseMigrations() {
        $containerId = $this->httpRequest->get('containerId');
        $entryId = $this->httpRequest->get('entryId');

        try {
            if($containerId === null) {
                throw new RequiredAttributeIsNotSetException('containerId');
            }
            if($entryId === null) {
                throw new RequiredAttributeIsNotSetException('entryId');
            }

            $database = $this->app->containerDatabaseManager->getDatabaseByEntryId($entryId);

            $containerDb = $this->app->dbManager->getConnectionToDatabase($database->getName());

            $dmm = new DatabaseMigrationManager($this->app->containerDatabaseRepository->conn, $containerDb, $this->logger);
            $dmm->setContainer($containerId);
            
            $dmm->runMigrations();

            $this->flashMessage('Migrations on database \'' . $database->getName() . '\' successfully run.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not run migrations on database \'' . $database->getName() . '\'. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('listDatabases', ['containerId' => $containerId]));
    }
}

?>