<?php

namespace App\UI;

use App\Core\Router;

/**
 * Link Builder allows to easily make links.
 * 
 * @author Lukas Velek
 */
class LinkBuilder implements IRenderable {
    private array $elements;
    private string $text;
    private array $urlParts;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->elements = [];
        $this->text = '';
        $this->urlParts = [];
    }

    /**
     * Renders the class parameters to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        if(!empty($this->urlParts)) {
            $url = self::convertUrlArrayToString($this->urlParts);
            $this->setHref($url);
        }

        $code = '<a ' . $this->processElements() . '>' . $this->text . '</a>';

        return $code;
    }

    /**
     * Sets the link class attribute
     * 
     * @param string $class Class name
     */
    public function setClass(string $class) {
        $this->elements['class'] = $class;

        return $this;
    }

    /**
     * Sets the link href attribute
     * 
     * @param string $href Href
     */
    public function setHref(string $href) {
        $this->elements['href'] = $href;

        return $this;
    }

    /**
     * Sets the link href attribute from URL
     * 
     * @param array $url URL array
     */
    public function setUrl(array $url) {
        $this->urlParts = array_merge($this->urlParts, $url);

        return $this;
    }

    /**
     * Sets the link text
     * 
     * @param string $text Text
     */
    public function setText(string $text) {
        $this->text = $text;

        return $this;
    }

    /**
     * Sets the link style attribute
     * 
     * @param string $style Style
     */
    public function setStyle(string $style) {
        $this->elements['style'] = $style;

        return $this;
    }

    /**
     * Sets the link onclick attribute
     * 
     * @param string JS onclick method name
     */
    public function setOnclick(string $onclickMethod) {
        $this->elements['onclick'] = $onclickMethod;

        return $this;
    }

    /**
     * Sets the link title attribute
     * 
     * @param string Title attribute
     */
    public function setTitle(string $title) {
        $this->elements['title'] = $title;

        return $this;
    }

    /**
     * Processes all the link attributes
     * 
     * @return string Link attributes
     */
    private function processElements() {
        $tmp = [];
        $tmpSingles = [];

        foreach($this->elements as $k => $v) {
            if($v === null) {
                $tmpSingles[] = $k;
            } else {
                $tmp[] = $k . '="' . $v . '"';
            }
        }

        $tmp = array_merge($tmp, $tmpSingles);

        return implode(' ', $tmp);
    }

    /**
     * Creates a simple link
     * 
     * @param string $text Link text
     * @param array $URL Link URL
     * @param string $class Link class
     * @return string HTML code
     */
    public static function createSimpleLink(string $text, array $url, string $class) {
        $obj = self::createSimpleLinkObject($text, $url, $class);

        return $obj->render();
    }

    /**
     * Creates a simple link and returns self
     * 
     * @param string $text Link text
     * @param array $URL Link URL
     * @param string $class Link class
     */
    public static function createSimpleLinkObject(string $text, array $url, string $class) {
        $lb = new self();

        $lb ->setText($text)
            ->setUrl($url)
            ->setClass($class);

        return $lb;
    }

    /**
     * Converts URL array to string
     * 
     * @param array $url
     * @return string URL
     */
    public static function convertUrlArrayToString(array $url) {
        return Router::generateUrl($url);
    }

    /**
     * Creates a JS link with onclick attribute
     * 
     * @param string $text Link text
     * @param string $jsMethod JS method called on onclick action
     * @param string $class Link class
     * @return string HTML code
     */
    public static function createJSOnclickLink(string $text, string $jsMethod, string $class) {
        $lb = new self();

        $lb ->setText($text)
            ->setOnclick($jsMethod)
            ->setClass($class)
            ->setHref('#');

        return $lb->render();
    }
}

?>