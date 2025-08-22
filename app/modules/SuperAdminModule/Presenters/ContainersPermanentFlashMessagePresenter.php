<?php

namespace App\Modules\SuperAdminModule;

use App\Constants\ContainerPermanentFlashMessageTypes;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Exceptions\AException;
use App\Helpers\LinkHelper;
use App\UI\GridBuilder2\Action;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ContainersPermanentFlashMessagePresenter extends ASuperAdminPresenter {
    public function __construct() {
        parent::__construct('ContainersPermanentFlashMessagePresenter', 'Containers permanent flash messages');
    }

    public function renderList() {
        $links = [
            $this->createBackFullUrl('SuperAdmin:Containers', 'list'),
            LinkBuilder::createSimpleLink('New message', $this->createURL('newMessageForm'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentPermanentFlashMessagesGrid() {
        $grid = $this->componentFactory->getGridBuilder();

        $qb = $this->app->containerPermanentFlashMessagesRepository->composeQueryForPermanentFlashMessages();
        $qb->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'messageId');

        $grid->addColumnUser('userId', 'Author');
        $grid->addColumnText('message', 'Message');
        $grid->addColumnConst('type', 'Message type', ContainerPermanentFlashMessageTypes::class);
        $grid->addColumnDatetime('dateValidUntil', 'Valid until');
        $grid->addColumnBoolean('isActive', 'Is active');

        $disable = $grid->addAction('disabled');
        $disable->setTitle('Disable');
        $disable->onCanRender[] = function(DatabaseRow $row, Row $_row, Action &$action) {
            if($row->isActive == false) {
                return false;
            }

            return true;
        };
        $disable->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a');

            $el->text('Disable')
                ->class('grid-link')
                ->href($this->createURLString('disableFlashMessage', ['messageId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function renderNewMessageForm() {
        $links = [
            $this->createBackUrl('list')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);

        $this->addScript('
            $("#message").on("blur", function (e) {
                const text = $(this).val();

                if(text.length >= 256) {
                    alert("Entered message is longer than allowed maximum of 256 characters.");
                    $(this).css("border", "1px solid red");
                    $("#formSubmit").attr("disabled");
                } else if(text.length == 0) {
                    alert("No message has been entered.");
                    $(this).css("border", "1px solid red");
                    $("#formSubmit").attr("disabled");
                } else {
                    $(this).css("border", "");
                    $("#formSubmit").removeAttr("disabled");
                }
            });
        ');
    }

    protected function createComponentNewMessageForm() {
        $now = DateTime::nowAsObject();
        $now->modify('+1d');
        $now->format('Y-m-d');
        $now = $now->getResult();

        $types = [
            [
                'value' => ContainerPermanentFlashMessageTypes::PERMANENT,
                'text' => ContainerPermanentFlashMessageTypes::toString(ContainerPermanentFlashMessageTypes::PERMANENT)
            ]
        ];

        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newMessageFormSubmit'));

        $form->addTextArea('message', 'Message:')
            ->setLines(3)
            ->setRequired();

        $form->addSelect('type', 'Type:')
            ->addRawOptions($types)
            ->setRequired();

        $form->addDateInput('dateValidUntil', 'Valid until:')
            ->setRequired()
            ->setValue($now);

        $form->addCheckboxInput('disablePrevious', 'Disable previous flash messages?');

        $form->addSubmit('Create');

        return $form;
    }

    public function handleNewMessageFormSubmit(FormRequest $fr) {
        try {
            $this->app->containerPermanentFlashMessagesRepository->beginTransaction(__METHOD__);

            $messageId = $this->app->containerManager->createNewContainerPermanentFlashMessage(
                $this->getUserId(),
                $fr->message,
                $fr->type,
                $fr->dateValidUntil
            );

            if($fr->disablePrevious == 'on') {
                $this->app->containerManager->disablePreviousContainerPermanentFlashMessages($messageId);
            }

            $this->app->containerPermanentFlashMessagesRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully created a new permanent flash message.', 'success');
        } catch(AException $e) {
            $this->app->containerPermanentFlashMessagesRepository->rollback(__METHOD__);

            $this->flashMessage('Could not create a new permanent flash message. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDisableFlashMessage() {
        $this->mandatoryUrlParams(['messageId'], $this->createURL('list'));

        $messageId = $this->httpRequest->get('messageId');

        try {
            $this->app->containerPermanentFlashMessagesRepository->beginTransaction(__METHOD__);

            $this->app->containerManager->updateContainerPermanentFlashMessage($messageId, ['isActive' => 0]);

            $this->app->containerPermanentFlashMessagesRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Successfully disabled permanent flash message.', 'success');
        } catch(AException $e) {
            $this->app->containerPermanentFlashMessagesRepository->rollback(__METHOD__);

            $this->flashMessage('Could not disable permanent flash message. Reason: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('list'));
    }
}