<?php

namespace App\Core\Http\Ajax\Operations;

/**
 * Operation for custom JS performed around an AJAX call
 * 
 * @author Lukas Velek
 */
class CustomOperation implements IAjaxOperation {
    private array $code;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->code = [];
        return $this;
    }

    /**
     * Adds code
     * 
     * @param string $code
     */
    public function addCode(IAjaxOperation|string $code): static {
        if($code instanceof IAjaxOperation) {
            $code = $code->build();
        }
        $this->code[] = $code;
        return $this;
    }

    public function build(): string {
        return implode("\r\n", $this->code);
    }
}

?>