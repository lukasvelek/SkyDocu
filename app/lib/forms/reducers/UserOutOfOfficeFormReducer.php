<?php

namespace App\Lib\Forms\Reducers;

use App\UI\FormBuilder2\ABaseFormReducer;
use App\UI\FormBuilder2\FormState\FormStateList;

/**
 * Default reducer for the User out-of-office form
 * 
 * @author Lukas Velek
 */
class UserOutOfOfficeFormReducer extends ABaseFormReducer {
    public function applyReducer(FormStateList &$stateList) {
        /*if(!$this->request->isAjax) {
            return;
        }*/

        if($stateList->dateFrom->value !== null) {
            // dateFrom must be more or equal to the current date
            if(strtotime($stateList->dateFrom->value) < strtotime(date('Y-m-d'))) {
                $stateList->dateFrom->value = date('Y-m-d');
            }
            
            // dateTo minimum must be value of dateFrom
            $stateList->dateTo->minimum = $stateList->dateFrom->value;
            
            if($stateList->dateTo->value !== null) {
                // dateTo must be greater than dateFrom
                if(strtotime($stateList->dateTo->value) < strtotime($stateList->dateFrom->value)) {
                    $stateList->dateTo->value = $stateList->dateFrom->value;
                }
            }
        }
    }

    public function applyOnStartupReducer(FormStateList &$stateList) {}
}

?>