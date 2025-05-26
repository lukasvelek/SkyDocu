<?php

namespace App\Entities;

class ProcessDefinitionEntity {
    private string $name;
    private array $forms;

    public function __construct(string $name, array $forms) {
        $this->name = $name;
        $this->forms = $forms;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getForms(): array {
        return $this->forms;
    }
}

?>