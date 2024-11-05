<?php

namespace App\UI\FormBuilder2;

use App\Core\Http\HttpRequest;
use App\UI\AComponent;

class FormBuilder2 extends AComponent {
    private array $elements;
    private array $labels;
    
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->elements = [];
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '/form.html');
        
        return $template->render()->getRenderedContent();
    }

    public function addTextInput(string $name, ?string $label = null, mixed $value = null) {
        $ti = new TextInput($name, $value);

        $this->elements[$name] = &$ti;

        if($label !== null) {
            $lbl = new Label($name, $label);
            $this->labels[$name] = $lbl;
        }

        return $ti;
    }

    public static function createFromComponent(AComponent $component) {
        
    }
}

?>