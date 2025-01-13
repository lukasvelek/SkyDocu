<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\StandaloneProcesses;
use App\UI\AComponent;

/**
 * FunctionRequest represents the FunctionRequest standalone process
 * 
 * @author Lukas Velek
 */
class FunctionRequest extends AProcessForm {
    protected function createForm() {
        $this->addTextArea('description', 'Description:')
            ->setRequired();

        $this->addSelect('user', 'User:')
            ->addRawOption($this->presenter->getUserId(), $this->presenter->getUser()->getFullname(), true)
            ->isDisabled();

        $this->addSubmit();
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::FUNCTION_REQUEST;

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