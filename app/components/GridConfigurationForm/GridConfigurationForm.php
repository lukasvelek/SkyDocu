<?php

namespace App\Components\GridConfigurationForm;

use App\Constants\Container\DocumentsGridSystemMetadata;
use App\Constants\Container\GridNames;
use App\Core\AjaxRequestBuilder;
use App\Core\Http\HttpRequest;
use App\Managers\Container\GridManager;
use App\UI\FormBuilder2\FormBuilder2;

class GridConfigurationForm extends FormBuilder2 {
    private GridManager $gm;

    public function __construct(HttpRequest $request, GridManager $gm) {
        parent::__construct($request);

        $this->componentName = 'GridConfigurationForm';

        $this->gm = $gm;
    }

    public function startup() {
        parent::startup();

        $this->setAction($this->createFullURL('Admin:GridConfiguration', 'newConfigurationForm'));

        $this->createScripts();
        $this->createForm();
    }

    private function createForm() {
        $this->addSelect('gridName', 'Grid:')
            ->addRawOptions($this->getGridsWithoutConfiguration());

        $this->addButton('Load columns')
            ->setOnClick('processLoadColumns()');

        $this->addLayoutSection('dynamicFormContent');
    }

    private function createScripts() {
        $addScript = function(string|AjaxRequestBuilder $code) {
            if($code instanceof AjaxRequestBuilder) {
                $code = $code->build();
            }

            $this->addScript($code);
        };

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-loadColumns')
            ->setHeader(['gridName' => '_gridName'])
            ->setFunctionName('loadColumns')
            ->setFunctionArguments(['_gridName'])
            ->updateHTMLElement('dynamicFormContent', 'content');

        $addScript($arb);

        $addScript('
            $("#dynamicFormContent").html("<div id=\'center\'>No grid selected.</div>");
        ');

        $addScript('
            function processLoadColumns() {
                const _gridName = $("#gridName").val();

                loadColumns(_gridName);
            }
        ');
    }

    private function getGridsWithoutConfiguration() {
        return $this->gm->getGridsWithNoConfiguration(true);
    }

    protected function actionLoadColumns() {
        $gridName = $this->httpGet('gridName');

        switch($gridName) {
            case GridNames::DOCUMENTS_GRID:
                $columns = DocumentsGridSystemMetadata::getAll();
                
                foreach($columns as $value => $text) {
                    $this->addCheckboxInput($value, $text . ':')
                        ->setChecked();
                }

                $this->addSubmit('Save');
                
                $elements = [];
                foreach($columns as $value => $text) {
                    $elements[$value] = $this->getElement($value);
                }

                $elements['btn_submit'] = $this->getElement('btn_submit');

                $codes = [];
                foreach($elements as $name => $element) {
                    $row = $this->buildElement($name, $element);
                    $codes[] = $row->render();
                }

                $code = implode('', $codes);

                break;
        }

        return ['content' => $code];
    }
}

?>