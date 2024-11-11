<?php

namespace App\Components\DocumentMetadataForm;

use App\Constants\Container\CustomMetadataTypes;
use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\FormBuilder2;

class DocumentMetadataForm extends FormBuilder2 {
    public function __construct(HttpRequest $request) {
        parent::__construct($request);
    }

    public function render() {
        $this->setup();

        return parent::render();
    }

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
            ->setRequired()
            ->addRawOptions($types)
            ->onChange('hideDefaultValueSelect()');

        $this->addTextInput('defaultValue', 'Default value:')
            ->setRequired()
            ->setHidden();

        $this->addSelect('defaultValueBoolean', 'Default value:')
            ->setRequired()
            ->addRawOption('0', 'False')
            ->addRawOption('1', 'True')
            ->setHidden();

        $this->addDateTimeInput('defaultValueDatetime', 'Default value:')
            ->setRequired()
            ->setHidden();

        $this->addNumberInput('defaultValueNumber', 'Default value:')
            ->setRequired()
            ->setHidden();

        $this->addCheckboxInput('isRequired', 'Is required?');

        $this->addSubmit();
    }
}

?>