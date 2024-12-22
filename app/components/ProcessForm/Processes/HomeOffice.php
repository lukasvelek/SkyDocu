<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;

class HomeOffice extends AProcessForm {
    public function __construct(HttpRequest $request, array $baseUrl) {
        parent::__construct($request, $baseUrl);
    }

    protected function createForm() {
        $this->addTextArea('reason', 'Reason:')
            ->setRequired();

        $this->addDateInput('dateFrom', 'Date from:')
            ->setRequired();

        $this->addDateInput('dateTo', 'Date to:')
            ->setRequired();

        $this->addSubmit('Start');
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::HOME_OFFICE;

        $this->setAction($url);
    }
}

?>