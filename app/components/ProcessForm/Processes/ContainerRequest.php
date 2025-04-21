<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\StandaloneProcesses;
use App\Constants\ContainerEnvironments;

/**
 * ContainerRequest represents the ContainerRequest standalone process
 * 
 * @author Lukas Velek
 * @deprecated since 1.6
 */
class ContainerRequest extends AProcessForm {
    protected function createForm() {
        $environments = ContainerEnvironments::getAll();
        $environmentsSelect = [];
        foreach($environments as $key => $title) {
            $environmentsSelect[] = [
                'value' => $key,
                'text' => $title
            ];
        }

        $this->addTextInput('containerName', 'Container name:')
            ->setRequired();

        $this->addSelect('environment', 'Environment:')
            ->addRawOptions($environmentsSelect)
            ->setRequired();

        $this->addTextArea('reason', 'Reason:')
            ->setRequired();

        $this->addTextArea('additionalNotes', 'Additional notes:');

        $this->addSubmit();
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::CONTAINER_REQUEST;

        $this->setAction($url);
    }
}

?>