<?php

namespace App\Components\ProcessForm\Processes\Reducers;

use App\Constants\Container\InvoiceCurrencies;
use App\UI\FormBuilder2\ABaseFormReducer;
use App\UI\FormBuilder2\FormState\FormStateList;

/**
 * InvoiceReducer is used with the Invoice process form
 * 
 * @author Lukas Velek
 */
class InvoiceReducer extends ABaseFormReducer {
    public function applyReducer(FormStateList &$stateList) {}

    public function applyOnStartupReducer(FormStateList &$stateList) {
        $stateList->invoiceNo->value = $this->generateInvoiceNo();
        $stateList->sumCurrency->selectValues = $this->getCurrencies();
    }

    private function getCurrencies() {
        $values = InvoiceCurrencies::getAll();

        $options = [];
        foreach($values as $key => $value) {
            $options[] = [
                'value' => $key,
                'text' => $value
            ];
        }

        return $options;
    }

    private function generateInvoiceNo() {
        $this->throwContainerIsNull();

        $processId = $this->request->get('processId');
        
        $lastInstance = $this->container->processInstanceManager->getLastInstanceForProcessId($processId, false);
        
        if($lastInstance !== null) {

        } else {
            // generate
            // format - xxxx/YYYY

            return '0001/' . date('Y');
        }
        
        // TODO: Implement invoice number generation - as in \App\Components\ProcessForm\Processes\Invoice
    }
}

?>