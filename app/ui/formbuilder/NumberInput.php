<?php

namespace App\UI\FormBuilder;

class NumberInput extends AInput {
    public function __construct(string $name, mixed $value, ?int $min = 0, ?int $max = 100) {
        parent::__construct('number');

        $this->name = $name;
        $this->id = $name;

        if($value !== null) {
            $this->value = $value;
        }
        if($min !== null) {
            $this->min = $min;
        }
        if($max !== null) {
            $this->max = $max;
        }
    }
}

?>