<?php

namespace App\Components\ProcessForm\Processes;

use App\Components\ProcessForm\Processes\Reducers\HomeOfficeReducer;
use App\Constants\Container\StandaloneProcesses;
use App\UI\AComponent;

/**
 * HomeOffice represents the HomeOffice standalone process
 * 
 * @author Lukas Velek
 * @deprecated since 1.6
 */
class HomeOffice extends AProcessForm {
    protected function createForm() {
        $this->addTextArea('reason', 'Reason:')
            ->setRequired();

        $this->addDateInput('dateFrom', 'Date from:')
            ->setRequired();

        $this->addDateInput('dateTo', 'Date to:')
            ->setRequired();

        $this->addSubmit('Start');

        $this->setCallReducerOnChange();
        $this->reducer = new HomeOfficeReducer($this->app, $this->httpRequest);
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::HOME_OFFICE;

        $this->setAction($url);
    }

    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);
        return $obj;
    }
}

?>