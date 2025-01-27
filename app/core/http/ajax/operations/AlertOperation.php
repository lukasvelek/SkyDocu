<?php

namespace App\Core\Http\Ajax\Operations;

/**
 * Operation for alerting user around an AJAX call
 * 
 * @author Lukas Velek
 */
class AlertOperation implements IAjaxOperation {
    private string $message;

    /**
     * Class constructor
     * 
     * @param string $message Message displayed to the user
     */
    public function __construct(string $message) {
        $this->message = $message;
        return $this;
    }

    public function build(): string {
        return 'alert("' . $this->message . '");';
    }
}

?>