<?php

namespace App\UI\FormBuilder2;

class Input extends AElement {
    private string $type;
    private string $name;

    public function __construct(string $type, string $name, ?string $value = null) {
        parent::__construct();

        $this->type = $type;
        $this->name = $name;

        if($value !== null) {
            $this->addAttribute('value', $value);
        }
    }

    public function render() {
        $code = '<input type="' . $this->type . '" ';

        if(!array_key_exists('name', $this->attributes)) {
            $this->addAttribute('name', $this->name);
        }
        if(!array_key_exists('id', $this->attributes)) {
            $this->addAttribute('id', $this->name);
        }

        $nullValues = [];
        foreach($this->attributes as $name => $value) {
            if($value === null) {
                $nullValues[] = $name;
            } else {
                $code .= $name . '="' . $value . '" ';
            }
        }

        if(!empty($nullValues)) {
            $code .= implode(' ', $nullValues);
        }

        $code .= '>';

        return $code;
    }
}

?>