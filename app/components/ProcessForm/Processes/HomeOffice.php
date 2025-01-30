<?php

namespace App\Components\ProcessForm\Processes;

use App\Components\ProcessForm\Processes\Reducers\HomeOfficeReducer;
use App\Constants\Container\StandaloneProcesses;
use App\UI\AComponent;

/**
 * HomeOffice represents the HomeOffice standalone process
 * 
 * @author Lukas Velek
 */
class HomeOffice extends AProcessForm {
    public function startup() {    
        parent::startup();
    }

    protected function createForm() {
        $this->addTextArea('reason', 'Reason:')
            ->setRequired();

        $this->addDateInput('dateFrom', 'Date from:')
            ->setRequired();

        $this->addDateInput('dateTo', 'Date to:')
            ->setRequired();

        $this->addSubmit('Start');

        $this->setCallReducerOnChange();
        $this->reducer = new HomeOfficeReducer($this->httpRequest);
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