<?php

namespace App\Modules\UserModule;

use App\Constants\Container\StandaloneProcesses;
use App\Core\DB\DatabaseRow;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Managers\EntityManager;
use App\Repositories\Container\PropertyItemsRepository;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class PropertyItemsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('PropertyItemsPresenter', 'Property items');
    }

    public function handleNewPropertyItemForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $title = $fr->title;
            $title2 = $fr->code;
            $title3 = null;

            try {
                $this->processRepository->beginTransaction(__METHOD__);

                $metadata = $this->standaloneProcessManager->getProcessMetadataByTitleAndProcessTitle('items', StandaloneProcesses::REQUEST_PROPERTY_MOVE);

                $itemId = $this->standaloneProcessManager->createMetadataEnumValue($metadata->metadataId, $title, $title2, $title3);

                $propertyItemsRepository = new PropertyItemsRepository($this->gridRepository->conn, $this->logger);

                $relationId = $this->processManager->createId(EntityManager::C_PROPERTY_ITEMS_USER_RELATION);

                if(!$propertyItemsRepository->createNewUserItemRelation($relationId, $this->getUserId(), $itemId)) {
                    throw new GeneralException('Database error.');
                }

                $this->processRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Successfully created a new property item.', 'success');
            } catch(AException $e) {
                $this->processRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create a new property item. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createFullURL('User:Reports', 'list', ['view' => 'propertyItems-all']));
        }
    }

    public function renderNewPropertyItemForm() {
        $this->template->links = $this->createBackUrl('list', ['view' => 'propertyItems-all']);
    }

    protected function createComponentNewPropertyItemForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $form->setAction($this->createURL('newPropertyItemForm'));

        $form->addTextInput('code', 'Inventory number:')
            ->setRequired();

        $form->addTextInput('title', 'Title:')
            ->setRequired();

        $form->addSubmit('Create');
        
        return $form;
    }

    public function handleHistoryList() {
        if($this->httpRequest->get('itemId') === null) {
            throw new RequiredAttributeIsNotSetException('itemId');
        }
    }

    public function renderHistoryList() {
        $this->template->links = $this->createBackFullUrl('User:Reports', 'list', ['view' => 'propertyItems-all']);
    }

    protected function createComponentPropertyItemHistoryGrid(HttpRequest $request) {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $propertyItemsRepository = new PropertyItemsRepository($this->gridRepository->conn, $this->logger);

        $items = $this->standaloneProcessManager->getProcessMetadataEnumValues(StandaloneProcesses::REQUEST_PROPERTY_MOVE, 'items');

        $itemData = [];
        foreach($items as $item) {
            $itemData[$item->valueId] = [
                'title' => $item->title,
                'title2' => $item->title2,
                'title3' => $item->title3
            ];
        }

        $qb = $propertyItemsRepository->composeQueryForPropertyItems(false);
        $qb->orderBy('dateCreated', 'DESC');

        $qb2 = clone $qb;
        $dataDb = $qb2->execute()->fetchAll();

        $users = [];
        foreach($dataDb as $row) {
            $users[] = $this->app->userManager->getUserById($row['userId']);
        }

        $grid->createDataSourceFromQueryBuilder($qb, 'relationId');

        $col = $grid->addColumnUser('oldUser', 'Old user');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($users) {
            $i = 0;
            foreach($users as $user) {
                if($user->getId() == $row->userId) {
                    if($i >= 0) {
                        if(count($users) > ($i + 1)) {
                            return $users[$i + 1]->getFullname();
                        }
                    }
                }

                $i++;
            }
        };

        $grid->addColumnUser('userId', 'New user');
        
        $col = $grid->addColumnText('itemId', 'Item');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($itemData) {
            $item = $itemData[$row->itemId];

            return $item['title'] . ' (' . $item['title2'] . ')';
        };

        $grid->addColumnDatetime('dateCreated', 'Date assigned');

        return $grid;
    }
}

?>