<?php

namespace App\Modules\AdminModule;

class TransactionLogPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('TransactionLogPresenter', 'Transaction log');

        $this->setSystem();
    }

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentTransactionLogGrid() {
        $grid = $this->componentFactory->getGridBuilder($this->containerId);

        $qb = $this->transactionLogRepository->composeQueryForTransactionLog();
        $qb->orderBy('dateCreated', 'DESC');

        $grid->createDataSourceFromQueryBuilder($qb, 'transactionId');

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnText('callingMethod', 'Method');
        $grid->addColumnDatetime('dateCreated', 'Date');

        $usersDb = $this->transactionLogRepository->getUserIdsInTransactionLog();
    
        $users = [];
        foreach($usersDb as $userId) {
            $user = $this->app->userManager->getUserById($userId);

            $users[$userId] = $user->getFullname();
        }

        $grid->addFilter('userId', null, $users);

        return $grid;
    }
}

?>