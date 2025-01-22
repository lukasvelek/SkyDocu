<?php

namespace App\Core\Http\Ajax\Requests;

use App\Core\Http\HttpRequest;

/**
 * PostAjaxRequest is a class for simple AJAX POST request creation.
 * 
 * @author Lukas Velek
 */
class PostAjaxRequest extends AAjaxRequest {
    private array $data;

    /**
     * Class constructor
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->data = [];
        return $this;
    }

    /**
     * Sets the request's data payload
     * 
     * @param array $data Data
     * @param bool $append True if the $data should be appended or false if it should overwrite
     */
    public function setData(array $data, bool $append = false): static {
        if($append) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data = $data;
        }
        return $this;
    }

    public function build(): string {
        $code = '$.post("' . $this->processUrl() . '", ' . $this->processData() . ').done(function(data) {';
        $code .= 'const obj = JSON.parse(data);';
        $code .= 'if(obj.error) {';
        $code .= 'alert("Request could not be completed. Reason: " + obj.errorMsg);';
        $code .= '} else {';

        foreach($this->onFinishOperations as $operation) {
            $code .= $operation->build();
        }

        $code .= '}});';

        return $this->internalBuild($code);
    }

    /**
     * Processes data payload
     */
    private function processData(): string {
        $json = json_encode($this->data);

        foreach($this->arguments as $argument) {
            $json = str_replace('"' . $argument . '"', $argument, $json);
        }

        return $json;
    }
}

?>