<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\StandaloneProcesses;

/**
 * ContainerRequest represents the ContainerRequest standalone process
 * 
 * @author Lukas Velek
 */
class ContainerRequest extends AProcessForm {
    protected function createForm() {
        
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::CONTAINER_REQUEST;

        $this->setAction($url);
    }
}

?>