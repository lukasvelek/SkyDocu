<?php

namespace App\UI\FormBuilder2;

/**
 * Form file input
 * 
 * @author Lukas Velek
 */
class FileInput extends AInput {
    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('file', $name);
    }
}

?>