<?php

namespace App\Modules\UserModule;

use App\Components\ProcessReportsSelect\ProcessReportsSelect;

class ReportsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('ReportsPresenter', 'Reports');
    }

    public function renderList() {}

    protected function createComponentReportsSelect() {
        $select = new ProcessReportsSelect($this->httpRequest, $this->standaloneProcessManager);

        return $select;
    }
}

?>