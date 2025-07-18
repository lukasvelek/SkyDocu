<?php

namespace App\UI\FormBuilder2;

use App\UI\HTML\HTML;

/**
 * Form design element
 * 
 * @author Lukas Velek
 */
class DesignElement extends AElement {
    private string $tag;
    private bool $singleTag;
    private ?string $content;

    /**
     * Class constructor
     * 
     * @param string $tag Tag name
     * @param bool $singleTag True if it a single tag (<hr>) or multi tag (<p></p>)
     */
    public function __construct(string $tag, bool $singleTag = true) {
        parent::__construct();

        $this->tag = $tag;
        $this->singleTag = $singleTag;

        $this->content = null;
    }

    /**
     * Sets element's content
     * 
     * Useful only if single tag is false
     * 
     * @param HTML|string $content Content
     */
    public function setContent(string|HTML $content) {
        if($content instanceof HTML) {
            $content = $content->toString();
        }

        $this->content = $content;
    }

    public function render() {
        $code = '<' . $this->tag;

        $this->appendAttributesToCode($code);

        $code .= '>';

        if(!$this->singleTag) {
            $code .= $this->content;
            $code .= '</' . $this->tag . '>';
        }

        return $code;
    }
}

?>