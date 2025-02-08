<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\InvoiceCurrencies;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\HttpRequest;
use App\Managers\Container\StandaloneProcessManager;
use App\UI\AComponent;

/**
 * Invoice represents the Invoice standalone process
 * 
 * @author Lukas Velek
 */
class Invoice extends AProcessForm {
    private StandaloneProcessManager $standaloneProcessManager;

    public function __construct(HttpRequest $request, StandaloneProcessManager $standaloneProcessManager) {
        parent::__construct($request);

        $this->standaloneProcessManager = $standaloneProcessManager;
    }

    protected function createForm() {
        $this->addTextInput('invoiceNo', 'Invoice No.:')
            ->setRequired();

        $this->addSelect('company', 'Company:')
            ->setRequired()
            ->addRawOptions($this->getCompaniesOptionsForSelect());

        $this->addNumberInput('sum', 'Sum:')
            ->setRequired();

        $this->addSelect('sumCurrency', 'Sum currency:')
            ->setRequired()
            ->addRawOptions($this->getCurrencyOptionsForSelect());
        
        $this->addSubmit('Create');
    }

    private function getCurrencyOptionsForSelect() {
        $values = InvoiceCurrencies::getAll();

        $options = [];
        foreach($values as $value => $text) {
            $options[] = [
                'value' => $value,
                'text' => $text
            ];
        }

        return $options;
    }

    private function getCompaniesOptionsForSelect() {
        $values = $this->standaloneProcessManager->getProcessMetadataEnumValues('invoice', 'companies');

        $options = [];
        foreach($values as $value) {
            $options[] = [
                'value' => $value->metadataKey,
                'text' => $value->title
            ];
        }

        return $options;
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::INVOICE;

        $this->setAction($url);
    }

    public static function createFromComponent(AComponent $component) {}
}

?>