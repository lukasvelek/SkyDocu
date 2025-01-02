<?php

namespace App\Modules\SuperAdminModule;

use App\Components\ContainerUsageAverageResponseTimeGraph\ContainerUsageAverageResponseTimeGraph;
use App\Components\ContainerUsageStatsGraph\ContainerUsageStatsGraph;
use App\Components\ContainerUsageTotalResponseTimeGraph\ContainerUsageTotalResponseTimeGraph;
use App\Constants\ContainerInviteUsageStatus;
use App\Constants\ContainerStatus;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\IRenderable;
use App\UI\LinkBuilder;

class ContainerSettingsPresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ContainerSettingsPresenter', 'Container settings');
    }

    public function renderHome() {}

    public function handleStatus(?FormResponse $fr = null) {
        $containerId = $this->httpGet('containerId', true);

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
        $this->template->links = [
            LinkBuilder::createSimpleLink('Show history', $this->createURL('listStatusHistory', ['containerId' => $this->httpGet('containerId')]), 'link')
        ];
    }

    protected function createComponentContainerStatusForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->query['containerId']);

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('status', ['containerId' => $request->query['containerId']]));

        $disabled = false;
        $statuses = [];
        foreach(ContainerStatus::getAll() as $key => $value) {
            if($container->status == ContainerStatus::NEW || $container->status == ContainerStatus::IS_BEING_CREATED) {
                $status = [
                    'text' => $value,
                    'value' => $key
                ];

                if($container->status == $key) {
                    $status['selected'] = 'selected';
                }

                $statuses[] = $status;
                $disabled = true;
            } else {
                if(in_array($key, [ContainerStatus::IS_BEING_CREATED, ContainerStatus::NEW, ContainerStatus::ERROR_DURING_CREATION])){
                    continue;
                }

                $status = [
                    'text' => $value,
                    'value' => $key
                ];

                if($container->status == $key) {
                    $status['selected'] = 'selected';
                }

                $statuses[] = $status;
            }
        }

        $form->addSelect('status', 'Status:')
            ->addRawOptions($statuses)
            ->setDisabled($disabled);

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addSubmit('Save');

        return $form;
    }

    protected function createComponentContainerPermanentFlashMessageForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->query['containerId']);

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('statusPermanentFlashMessage', ['containerId' => $request->query['containerId']]));

        $permanentFlashMessage = $form->addTextArea('permanentFlashMessage', 'Flash message text:')
            ->setRequired();

        $permanentFlashMessage->setContent($container->permanentFlashMessage);

        $form->addSubmit('Save');
        $form->addButton('Clear')
            ->setOnClick('location.href = \'' . $this->createURLString('statusClearPermanentFlashMessage', ['containerId' => $container->containerId]) . '\';');

        return $form;
    }

    public function handleStatusPermanentFlashMessage(?FormResponse $fr = null) {
        $containerId = $this->httpGet('containerId', true);
        
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
        $containerId = $this->httpGet('containerId', true);

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
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('status', ['containerId' => $this->httpGet('containerId')]), 'link')
        ];
    }

    protected function createComponentContainerStatusHistoryGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->containerRepository->composeQueryForContainerStatusHistory($request->query['containerId']), 'historyId');
        $grid->addQueryDependency('containerId', $request->query['containerId']);

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

    public function handleAdvanced() {
        $containerId = $this->httpGet('containerId', true);

        $containerDeleteLink = HTML::el('a')
            ->class('link')
            ->href($this->createURLString('containerDeleteForm', ['containerId' => $containerId]))
            ->style('color', 'red')
            ->text('Delete')
            ->title('Delete')
            ->toString()
        ;

        $this->saveToPresenterCache('containerDeleteLink', $containerDeleteLink);
    }

    public function renderAdvanced() {
        $this->template->container_delete_link = $this->loadFromPresenterCache('containerDeleteLink');
    }

    public function handleContainerDeleteForm(?FormResponse $fr = null) {
        $containerId = $this->httpGet('containerId');

        if($fr !== null) {
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                $container = $this->app->containerManager->getContainerById($containerId);

                if($fr->title != $container->title) {
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
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('advanced', ['containerId' => $this->httpGet('containerId')]), 'link')
        ];
    }

    protected function createComponentContainerDeleteForm(HttpRequest $request) {
        $container = $this->app->containerManager->getContainerById($request->query['containerId']);

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('containerDeleteForm', ['containerId' => $request->query['containerId']]));

        $form->addTextInput('title', 'Container title (\'' . $container->title . '\'):')
            ->setRequired();

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addSubmit('Delete');

        return $form;
    }

    public function renderUsageStatistics() {}

    protected function createComponentContainerUsageStatsGraph(HttpRequest $request) {
        $graph = new ContainerUsageStatsGraph($request, $this->app->containerRepository);

        $graph->setContainerId($request->query['containerId']);
        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentContainerUsageAverageResponseTimeGraph(HttpRequest $request) {
        $graph = new ContainerUsageAverageResponseTimeGraph($request, $this->app->containerRepository);

        $graph->setContainerId($request->query['containerId']);
        $graph->setCanvasWidth(400);

        return $graph;
    }

    protected function createComponentContainerUsageTotalResponseTimeGraph(HttpRequest $request) {
        $graph = new ContainerUsageTotalResponseTimeGraph($request, $this->app->containerRepository);

        $graph->setContainerId($request->query['containerId']);
        $graph->setCanvasWidth(400);

        return $graph;
    }

    public function handleInvites() {
        $containerId = $this->httpGet('containerId');

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
        $this->saveToPresenterCache('links', implode('&nbsp;|&nbsp;', $links));

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
    }

    public function renderInvites() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentContainerInvitesGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->containerInviteManager->composeQueryForContainerInviteUsages($request->query['containerId']);
        $qb->orderBy('status');
        
        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');
        $grid->addQueryDependency('containerId', $request->query['containerId']);

        $col = $grid->addColumnConst('status', 'Status', ContainerInviteUsageStatus::class);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');
            $el->style('color', ContainerInviteUsageStatus::getColor($row->status))
                ->text($value);

            return $el;
        };

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

    public function handleInvitesWithoutGrid() {
        $containerId = $this->httpGet('containerId', true);

        $inviteLink = LinkBuilder::createSimpleLink('Generate invite link', $this->createURL('generateInviteLink', ['containerId' => $containerId]), 'link');
        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', [$inviteLink]));
    }

    public function renderInvitesWithoutGrid() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function handleGenerateInviteLink() {
        $containerId = $this->httpGet('containerId', true);
        $regenerate = $this->httpGet('regenerate') !== null;

        $dateValid = new DateTime();
        $dateValid->modify('+1m');
        $dateValid = $dateValid->getResult();

        try {
            $this->app->containerInviteRepository->beginTransaction(__METHOD__);

            if($regenerate) {
                $inviteId = $this->httpGet('oldInviteId');

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
        $entryId = $this->httpGet('entryId', true);
        $containerId = $this->httpGet('containerId', true);

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
        $entryId = $this->httpGet('entryId', true);
        $containerId = $this->httpGet('containerId', true);

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
        $entryId = $this->httpGet('entryId', true);
        $containerId = $this->httpGet('containerId', true);

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
}

?>