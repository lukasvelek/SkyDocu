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
        if($stateList->invoiceNo->value === null) {
            $stateList->invoiceNo->value = $this->generateInvoiceNo();
            $stateList->invoiceNo->isReadonly = true;
        }
        if($stateList->sumCurrency->selectValues === null) {
            $stateList->sumCurrency->selectValues = $this->getCurrencies();
        }
    }

    public function applyAfterSubmitOnOpenReducer(FormStateList &$stateList) {
        // CURRENCY
        $currencies = InvoiceCurrencies::getAll();

        foreach($currencies as $key => $value) {
            if($stateList->sumCurrency->value == $key) {
                $stateList->sumCurrency->value = $value;
                break;
            }
        }

        // COMPANY
        $processId = $this->request->get('processId');
        $uniqueProcessId = $this->container->processManager->getUniqueProcessIdForProcessId($processId);

        $values = $this->container->processMetadataManager->getMetadataValuesForUniqueProcessId($uniqueProcessId, 'companies');

        foreach($values as $value) {
            if($value->metadataKey == $stateList->company->value) {
                $stateList->company->value = $value->title;
            }
        }
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
            $data = $lastInstance->data;
            $data = unserialize($data);

            $invoiceNo = $data['invoiceNo'];

            $order = explode('/', $invoiceNo)[0];
            $year = explode('/', $invoiceNo)[1];

            if($year == date('Y')) {
                $order = (int)$order;
                $order++;

                $order = (string)$order;
                while(strlen($order) < 4) {
                    $order = '0' . $order;
                }

                return $order . '/' . $year;
            }
        }

        return '0001/' . date('Y');
    }
}

?>