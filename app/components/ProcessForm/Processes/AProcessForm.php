<?php

namespace App\Components\ProcessForm\Processes;

use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\FormBuilder2;

/**
 * Common class for process forms
 * 
 * @author Lukas Velek
 */
abstract class AProcessForm extends FormBuilder2 {
    public array $baseUrl;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);
    }

    public function startup() {
        $this->createAction();
        $this->createForm();

        parent::startup();
    }

    /**
     * Creates form elements
     */
    protected abstract function createForm();

    /**
     * Creates form action
     */
    protected abstract function createAction();
}

?>