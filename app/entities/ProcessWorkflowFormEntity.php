<?php

namespace App\Entities;

use App\Exceptions\GeneralException;

class ProcessWorkflowFormEntity {
    private string $name;
    private ?string $instanceDescription;
    private array $elements;

    public function __construct(string $name, ?string $instanceDescription, array $elements) {
        $this->name = $name;
        $this->instanceDescription = $instanceDescription;
        $this->elements = $elements;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getInstanceDescription(): ?string {
        return $this->instanceDescription;
    }

    public function getElements(): array {
        return $this->elements;
    }

    public static function createFromArray(array $data) {
        $mandatory = [
            'name',
            'elements'
        ];

        foreach($mandatory as $m) {
            if(!array_key_exists($m, $data)) {
                throw new GeneralException(sprintf('Parameter %s is not defined.', $m));
            }
        }

        $name = $data['name'];
        $elements = $data['elements'];
        $instanceDescription = null;

        if(array_key_exists('instanceDescription', $data)) {
            $instanceDescription = $data['instanceDescription'];
        }

        return new self($name, $instanceDescription, $elements);
    }
}

?>