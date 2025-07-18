<?php

namespace App\Modules\UserModule;

use App\Components\ContactsSelect\ContactsSelect;

class ContactsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ContactsPresenter', 'Contacts');
    }

    public function renderContactsGrid() {}

    protected function createComponentContactSelect() {
        /**
         * @var ContactsSelect $select
         */
        $select = $this->componentFactory->createComponentInstanceByClassName(ContactsSelect::class);

        $select->setContainerId($this->containerId);

        return $select;
    }
}