<?php

namespace App\Components\ProcessForm\Processes\Reducers;

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
        
    }

    private function generateInvoiceNo() {
        $this->throwContainerIsNull();

        
        // TODO: Implement invoice number generation - as in \App\Components\ProcessForm\Processes\Invoice
    }
}

?>