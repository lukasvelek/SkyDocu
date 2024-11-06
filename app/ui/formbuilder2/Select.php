<?php

namespace App\UI\FormBuilder2;

/**
 * Form select
 * 
 * @author Lukas Velek
 */
class Select extends AInteractableElement {
    private string $name;
    /**
     * @var array<SelectOption> $options
     */
    private array $options;
    
    /**
     * Class constructor
     * 
     * @param string $name Select name
     */
    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;
        $this->options = [];
    }

    /**
     * Adds multiple raw options
     * Array has to look like this:
     * [
     *  [
     *    'value' => '',
     *    'text' => '',
     *    'selected' => 'selected'
     *  ],
     *  [
     *    'value' => '',
     *    'text' => ''
     *  ],
     *  ...
     * ]
     * 
     * @param array $options
     * @return self
     */
    public function addRawOptions(array $options) {
        foreach($options as $option) {
            $value = $option['value'];
            $text = $option['text'];
            $selected = array_key_exists('selected', $option);

            $this->addRawOption($value, $text, $selected);
        }

        return $this;
    }

    /**
     * Adds raw option
     * 
     * @param string $value Option value
     * @param string $text Option text
     * @param bool $isSelected Is the option selected?
     * @return self
     */
    public function addRawOption(string $value, string $text, bool $isSelected = false) {
        $option = new SelectOption($value, $text);

        if($isSelected) {
            $option->setSelected();
        }

        return $this->addOption($option);
    }

    /**
     * Adds option
     * 
     * @param SelectOption $option SelectOption instance
     * @return self
     */
    public function addOption(SelectOption $option) {
        $this->options[$option->getValue()] = $option;
        return $this;
    }

    /**
     * Adds multiple options
     * 
     * @param array<SelectOption> $options Array of SelectOption instances
     * @return self
     */
    public function addOptions(array $options) {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Returns instance of a SelectOption
     * 
     * @param string $value SelectOption value
     * @return ?SelectOption SelectOption or null
     */
    public function getOption(string $value) {
        if(array_key_exists($value, $this->options)) {
            $option = &$this->options[$value];
            return $option;
        } else {
            return null;
        }
    }

    /**
     * Renders the element to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<select name="' . $this->name . '">';

        foreach($this->options as $option) {
            $code .= $option->render();
        }

        $code .= '</select>';

        return $code;
    }
}

?>