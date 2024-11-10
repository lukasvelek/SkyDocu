<?php

namespace App\Modules\AdminModule;

class MembersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('MembersPresenter', 'Members');

        $this->setMembers();
    }

    public function renderDashboard() {}
}

?>