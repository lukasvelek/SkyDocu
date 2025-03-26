<?php

namespace App\Modules\AdminModule;

use App\Constants\Container\ExternalSystemLogActionTypes;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ExternalSystemsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ExternalSystemsPresenter', 'External systems');

        $this->setSystem();
    }

    public function renderList() {
        $links = [
            LinkBuilder::createSimpleLink('New external system', $this->createURL('newForm'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentContainerExternalSystemsGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $grid->createDataSourceFromQueryBuilder($this->externalSystemsRepository->composeQueryForExternalSystems(), 'systemId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnBoolean('isEnabled', 'Enabled');

        $info = $grid->addAction('info');
        $info->setTitle('Information');
        $info->onCanRender[] = function() {
            return true;
        };
        $info->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('info', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Information');

            return $el;
        };

        $log = $grid->addAction('log');
        $log->setTitle('Log');
        $log->onCanRender[] = function() {
            return true;
        };
        $log->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('log', ['systemId' => $primaryKey]))
                ->class('grid-link')
                ->text('Log');

            return $el;
        };

        $enable = $grid->addAction('enable');
        $enable->setTitle('Enable');
        $enable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isEnabled == false);
        };
        $enable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('change', ['systemId' => $primaryKey, 'operation' => 'enable']))
                ->class('grid-link')
                ->text('Enable');

            return $el;
        };

        $disable = $grid->addAction('disable');
        $disable->setTitle('Disable');
        $disable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            return ($row->isEnabled == true);
        };
        $disable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');
            $el->href($this->createURLString('change', ['systemId' => $primaryKey, 'operation' => 'disable']))
                ->class('grid-link')
                ->text('Disable');

            return $el;
        };

        return $grid;
    }

    public function handleNewForm(?FormRequest $fr = null) {
        if($fr !== null) {
            try {
                $this->externalSystemsRepository->beginTransaction(__METHOD__);

                $this->externalSystemsManager->createNewExternalSystem($fr->title, $fr->description, $fr->password);

                $this->externalSystemsRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully created new external system.', 'success');
            } catch(AException $e) {
                $this->externalSystemsRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create a new external system. Reason: ' . $e->getMessage(), 'error', 10);
            }
            
            $this->redirect($this->createURL('list'));
        }
    }

    public function renderNewForm() {
        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentNewExternalSystemForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newForm'));

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addTextArea('description', 'Description:')
            ->setRequired();

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addSubmit('Create');

        return $form;
    }

    public function renderLog() {
        $systemId = $this->httpRequest->get('systemId');
        if($systemId === null) {
            throw new RequiredAttributeIsNotSetException('systemId');
        }

        $this->template->links = $this->createBackUrl('list');
    }

    protected function createComponentContainerExternalSystemLogGrid(HttpRequest $request) {
        $systemId = $request->get('systemId');

        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->externalSystemLogRepository->composeQueryForExternalSystemLog();
        $qb->andWhere('systemId = ?', [$systemId])
            ->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'entryId');
        $grid->addQueryDependency('systemId', $systemId);

        $grid->addColumnText('message', 'Message');
        $grid->addColumnConst('actionType', 'Action', ExternalSystemLogActionTypes::class);
        $grid->addColumnConst('objectType', 'Object', ExternalSystemLogObjectTypes::class);
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }

    public function renderInfo() {
        $systemId = $this->httpRequest->get('systemId');
        if($systemId === null) {
            throw new RequiredAttributeIsNotSetException('systemId');
        }

        $texts = [];

        $add = function(string $title, string $text) use (&$texts) {
            $texts[] = "<p><b>$title:</b> $text</p>";
        };

        $system = $this->externalSystemsManager->getExternalSystemById($systemId);

        $add('Title', $system->title);
        $add('Description', $system->description);
        $add('Is enabled', ($system->isEnabled == true ? 'Yes' : 'No'));
        $add('Login', $system->login);
        $add('Password', LinkBuilder::createSimpleLink('Change password', $this->createURL('changePasswordForm', ['systemId' => $systemId]), 'link'));

        $this->template->external_system_basic_info = implode('', $texts);

        $this->template->links = $this->createBackUrl('list');
    }

    public function handleChange() {
        $systemId = $this->httpRequest->get('systemId');
        if($systemId === null) {
            throw new RequiredAttributeIsNotSetException('systemId');
        }
        $operation = $this->httpRequest->get('operation');

        try {
            $this->externalSystemsRepository->beginTransaction(__METHOD__);

            if($operation == 'enable') {
                $this->externalSystemsManager->enableExternalSystem($systemId);
            } else {
                $this->externalSystemsManager->disableExternalSystem($systemId);
            }

            $this->externalSystemsRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage(sprintf('Successfully %s external system.', ($operation == 'enable' ? 'enabled' : 'disabled')), 'success');
        } catch(AException $e) {
            $this->externalSystemsRepository->rollback(__METHOD__);

            $this->flashMessage(sprintf('Could not %s external system. Reason: %s', ($operation == 'enable' ? 'enable' : 'disable'), $e->getMessage()), 'error', '10');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleChangePasswordForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $systemId = $this->httpRequest->get('systemId');
            if($systemId === null) {
                throw new RequiredAttributeIsNotSetException('systemId');
            }

            try {
                $this->externalSystemsRepository->beginTransaction(__METHOD__);

                $password = password_hash($fr->password, PASSWORD_BCRYPT);

                $this->externalSystemsManager->updateExternalSystem($systemId, ['password' => $password]);

                $this->externalSystemsRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully changed external system\'s password.', 'success');
            } catch(AException $e) {
                $this->externalSystemsRepository->rollback(__METHOD__);

                $this->flashMessage('Could not change external system\'s password.', 'error', 10);
            }

            $this->redirect($this->createURL('info', ['systemId' => $systemId]));
        }
    }

    public function renderChangePasswordForm() {
        $systemId = $this->httpRequest->get('systemId');

        $this->template->links = $this->createBackUrl('info', ['systemId' => $systemId]);
    }

    protected function createComponentChangePasswordForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('changePasswordForm', ['systemId' => $request->get('systemId')]));

        $form->addPasswordInput('password', 'Password:')
            ->setRequired();

        $form->addSubmit('Save');

        return $form;
    }
}

?>