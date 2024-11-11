<?php

namespace App\Modules\AdminModule;

class DocumentsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('DocumentsPresenter', 'Documents');

        $this->setDocuments();
    }

    public function renderDashboard() {}
}

?>