<?php

namespace App\Modules\UserModule;

use App\Components\ContactsSelect\ContactsSelect;
use App\Components\UserInOrganizationChart\UserInOrganizationChart;

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

    public function renderUserInOrganization() {
        $this->template->links = $this->createBackUrl('contactsGrid');
    }

    protected function createComponentUserOrganizationChart() {
        $userId = $this->httpRequest->get('userId');

        /**
         * @var UserInOrganizationChart $component
         */
        $component = $this->componentFactory->createComponentInstanceByClassName(UserInOrganizationChart::class);

        $component->setUserId($userId);

        return $component;
    }
}