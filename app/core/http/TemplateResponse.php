<?php

namespace App\Core\Http;

use App\Modules\TemplateObject;

/**
 * Response that is used for sending template as response
 * 
 * @author Lukas Velek
 */
class TemplateResponse extends AResponse {
    public function __construct(TemplateObject $template) {
        parent::__construct($template);
    }

    public function getResult(): string {
        /**
         * @var TemplateObject $template
         */
        $template = $this->data;

        return $template->render()->getRenderedContent();
    }
}

?>