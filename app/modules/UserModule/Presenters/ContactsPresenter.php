<?php

namespace App\Modules\UserModule;

use App\Components\ContactsSelect\ContactsSelect;
use App\Components\OrganizationChart\OrganizationChart;
use App\Components\UserInOrganizationChart\UserInOrganizationChart;
use App\Helpers\LinkHelper;
use App\UI\LinkBuilder;

class ContactsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ContactsPresenter', 'Contacts');
    }

    public function renderContactsGrid() {
        $links = [
            LinkBuilder::createSimpleLink('Organization chart', $this->createURL('organizationChart'), 'link')
        ];

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

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

    public function renderOrganizationChart() {
        $links = [];

        if($this->httpRequest->get('userId') !== null) {
            $selectedUser = $this->app->userManager->getUserById($this->httpRequest->get('userId'));

            $params = [];
            if($selectedUser->getSuperiorUserId() !== null) {
                $params['userId'] = $selectedUser->getSuperiorUserId();
            }

            $links[] = LinkBuilder::createSimpleLink('&uarr; Up', $this->createURL('organizationChart', $params), 'link');
        }

        $this->template->links = LinkHelper::createLinksFromArray($links);
    }

    protected function createComponentOrganizationChart() {
        /**
         * @var OrganizationChart $component
         */
        $component = $this->componentFactory->createComponentInstanceByClassName(OrganizationChart::class);

        $component->setUserId($this->httpRequest->get('userId'));
        $component->setContainerId($this->containerId);

        return $component;
    }
}