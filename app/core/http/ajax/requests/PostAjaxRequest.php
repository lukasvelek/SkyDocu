<?php

namespace App\Core\Http\Ajax\Requests;

use App\Core\Http\HttpRequest;

/**
 * PostAjaxRequest is a class for simple AJAX POST request creation.
 * 
 * @author Lukas Velek
 */
class PostAjaxRequest extends AAjaxRequest {
    /**
     * Class constructor
     */
    public function __construct(HttpRequest $request) {
        parent::__construct($request);
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
            $data = array_merge($this->data, $data);
        }
        return parent::setData($data);
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
}

?>