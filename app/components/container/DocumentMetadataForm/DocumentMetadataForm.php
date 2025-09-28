<?php

namespace App\Components\DocumentMetadataForm;

use App\Constants\Container\CustomMetadataTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\FormBuilder2;

/**
 * DocumentMetadataForm is a form component used for creating and editing document metadata
 * 
 * @author Lukas Velek
 */
class DocumentMetadataForm extends FormBuilder2 {
    private ?DatabaseRow $metadata;

    public function __construct(HttpRequest $request) {
        parent::__construct($request);

        $this->metadata = null;
    }

    public function render() {
        $this->setup();

        if($this->metadata !== null) {
            $this->applyEdit();
        }

        return parent::render();
    }

    /**
     * Sets edited metadata
     * 
     * @param DatabaseRow $metadata Metadata DatabaseRow instance
     */
    public function setMetadata(DatabaseRow $metadata) {
        $this->metadata = $metadata;
    }

    /**
     * Appplies edit to the form
     */
    private function applyEdit() {
        /**
         * @var \App\UI\FormBuilder2\TextInput $title
         */
        $title = $this->getElement('title');
        $title->setValue($this->metadata->title);

        /**
         * @var \App\UI\FormBuilder2\TextInput $guiTitle
         */
        $guiTitle = $this->getElement('guiTitle');
        $guiTitle->setValue($this->metadata->guiTitle);

        /**
         * @var \App\UI\FormBuilder2\Select
         */
        $type = $this->getElement('type');
        $type->setSelectedValue($this->metadata->type);
        $type->alterOptionText($this->metadata->type, CustomMetadataTypes::toString($this->metadata->type) . ' (current)');

        if($this->metadata->defaultValue !== null) {
            switch($this->metadata->type) {
                case CustomMetadataTypes::BOOL:
                    /**
                     * @var \App\UI\FormBuilder2\Select $defaultValue
                     */
                    $defaultValue = $this->getElement('defaultValueBoolean');
                    $defaultValue->setSelectedValue($this->metadata->defaultValue);

                    break;

                case CustomMetadataTypes::DATE:
                    /**
                     * @var \App\UI\FormBuilder2\DateInput $defaultValue
                     */
                    $defaultValue = $this->getElement('defaultValueDate');
                    $defaultValue->setValue($this->metadata->defaultValue);

                    break;

                case CustomMetadataTypes::DATETIME:
                    /**
                     * @var \App\UI\FormBuilder2\DateTimeInput $defaultValue
                     */
                    $defaultValue = $this->getElement('defaultValueDatetime');
                    $defaultValue->setValue($this->metadata->defaultValue);
                    break;

                case CustomMetadataTypes::ENUM:
                    break;

                case CustomMetadataTypes::NUMBER:
                    /**
                     * @var \App\UI\FormBuilder2\NumberInput $defaultValue
                     */
                    $defaultValue = $this->getElement('defaultValueDate');
                    $defaultValue->setValue($this->metadata->defaultValue);
                    break;

                case CustomMetadataTypes::TEXT:
                    /**
                     * @var \App\UI\FormBuilder2\TextInput $defaultValue
                     */
                    $defaultValue = $this->getElement('defaultValue');
                    $defaultValue->setValue($this->metadata->defaultValue);
                    break;
            }
        }

        if($this->metadata->isRequired == '1') {
            /**
             * @var \App\UI\FormBuilder2\CheckboxInput $isRequired
             */
            $isRequired = $this->getElement('isRequired');
            $isRequired->setChecked();
        }
    }

    /**
     * Sets up the form
     */
    private function setup() {
        $this->addTextInput('title', 'Title:')
            ->setRequired()
            ->setPlaceholder('title');

        $this->addTextInput('guiTitle', 'GUI title:')
            ->setRequired()
            ->setPlaceholder('Title');

        $types = [];
        foreach(CustomMetadataTypes::getAll() as $value => $text) {
            $type = [
                'value' => $value,
                'text' => $text
            ];

            $types[] = $type;
        }

        $this->addSelect('type', 'Type:')
            ->addRawOptions($types)
            ->onChange('hideDefaultValueSelect()');

        $this->addTextInput('defaultValue', 'Default value:')
            ->setHidden();

        $this->addSelect('defaultValueBoolean', 'Default value:')
            ->addRawOption('0', 'False')
            ->addRawOption('1', 'True')
            ->setHidden();

        $this->addDateTimeInput('defaultValueDatetime', 'Default value:')
            ->setHidden();

        $this->addDateInput('defaultValueDate', 'Default value:')
            ->setHidden();

        $this->addNumberInput('defaultValueNumber', 'Default value:')
            ->setHidden();

        $this->addCheckboxInput('isRequired', 'Is required?');

        $this->addSubmit('Save');
    }
}

?>