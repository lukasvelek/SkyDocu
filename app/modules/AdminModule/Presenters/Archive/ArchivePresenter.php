<?php

namespace App\Modules\AdminModule;

class ArchivePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ArchivePresenter', 'Archive');

        $this->setArchive();
    }

    public function renderDashboard() {}
}

?>