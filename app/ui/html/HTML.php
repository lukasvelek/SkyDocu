<?php

namespace App\UI\HTML;

/**
 * HTML is a class that allows creating a HTML tag. It can be whatever tag as it supports several parameters.
 * 
 * @author Lukas Velek
 */
class HTML {
    private ?string $el;
    private array $styles;
    private null|HTML|string $text;
    private array $attributes;

    /**
     * Private class constructor
     */
    private function __construct() {
        $this->el = null;
        $this->styles = [];
        $this->text = null;
        $this->attributes = [];
        return $this;
    }

    /**
     * Sets the tag (a, span, div, ...)
     * 
     * @param string $name Tag
     */
    public static function el(string $name) {
        $x = new self();
        $x->el = $name;
        return $x;
    }

    /**
     * Changes the tag
     * 
     * @param string $name Tag
     */
    public function changeTag(string $name) {
        $this->el = $name;
        return $this;
    }

    /**
     * Adds custom attribute
     * 
     * @param string $key Attribute key
     * @param mixed $value Attribute value
     */
    public function addAtribute(string $key, mixed $value) {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Sets the class attribute
     * 
     * @param string $class
     */
    public function class(string $class) {
        $this->attributes['class'] = $class;
        return $this;
    }

    /**
     * Sets the style attribute
     * 
     * @param string $key Style key (color, background-color)
     * @param mixed $value Style value (green, 10px)
     */
    public function style(string $key, mixed $value) {
        $this->styles[] = $key . ': ' . $value;
        return $this;
    }

    /**
     * Sets the content - it can be string or another instance of HTML
     * 
     * @param HTML|string $text
     */
    public function text(HTML|string $text) {
        $this->text = $text;
        return $this;
    }

    /**
     * Returns the content
     * 
     * @return HTML|string Content
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Sets the title attribute
     * 
     * @param string $title
     */
    public function title(string $title) {
        $this->attributes['title'] = $title;
        return $this;
    }

    /**
     * Sets the href attribute
     * 
     * @param string $href Href
     */
    public function href(string $href) {
        $this->attributes['href'] = $href;
        return $this;
    }

    /**
     * Sets the onclick attribute
     * 
     * @param string $onClick Onclick
     */
    public function onClick(string $onClick) {
        $this->attributes['onclick'] = $onClick;
        return $this;
    }

    /**
     * Sets the value attribute
     * 
     * @param mixed $value Value
     */
    public function value(mixed $value, bool $isArray = false) {
        $key = 'value';
        if($isArray) {
            $key .= '[]';
        }
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Sets the name attribute
     * 
     * @param string $name Name
     */
    public function name(string $name) {
        $this->attributes['name'] = $name;
        return $this;
    }

    /**
     * Sets the id attribute
     * 
     * @param string $id ID
     */
    public function id(string $id) {
        $this->attributes['id'] = $id;
        return $this;
    }

    /**
     * Sets the target
     * 
     * @param string $target
     */
    public function target(string $target) {
        $this->attributes['target'] = $target;
        return $this;
    }

    /**
     * Converts the class to string
     * 
     * @return string
     */
    public function toString() {
        $code = '<' . $this->el;

        $tmps = [];
        foreach($this->attributes as $key => $value) {
            $tmp = $key;

            if($value !== null) {
                $tmp .= '="' . $value . '"';
                $tmps[] = $tmp;
            } else {
                $tmps = array_merge([$tmp], $tmps);
            }
        }

        $code .= ' ' . implode(' ', $tmps);

        if(!empty($this->styles)) {
            $code .= ' style="' . implode('; ', $this->styles) . '"';
        }

        $code .= '>';

        if($this->text instanceof HTML) {
            $code .= $this->text->toString();
        } else {
            $code .= $this->text;
        }

        $code .= '</' . $this->el . '>';

        return $code;
    }
}

?>