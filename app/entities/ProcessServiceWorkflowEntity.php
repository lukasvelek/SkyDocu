<?php

namespace App\Entities;

use App\Exceptions\GeneralException;

class ProcessServiceWorkflowEntity {
    private string $name;
    private ?string $instanceDescription;
    private ?int $status;

    public function __construct(string $name, ?string $instanceDescription, ?int $status) {
        $this->name = $name;
        $this->instanceDescription = $instanceDescription;
        $this->status = $status;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getInstanceDescription(): ?string {
        return $this->instanceDescription;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public static function createFromArray(array $data) {
        $mandatory = [
            'name'
        ];

        foreach($mandatory as $m) {
            if(!array_key_exists($m, $data)) {
                throw new GeneralException(sprintf('Parameter %s is not defined.', $m));
            }
        }

        $name = $data['name'];
        $instanceDescription = null;
        $status = null;

        if(array_key_exists('instanceDescription', $data)) {
            $instanceDescription = $data['instanceDescription'];
        }
        if(array_key_exists('status', $data)) {
            $status = $data['status'];
        }

        return new self($name, $instanceDescription, $status);
    }
}

?>