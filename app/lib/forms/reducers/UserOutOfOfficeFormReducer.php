<?php

namespace App\Lib\Forms\Reducers;

use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\FormState\FormStateList;
use App\UI\FormBuilder2\IFormReducer;

/**
 * Default reducer for the User out-of-office form
 * 
 * @author Lukas Velek
 */
class UserOutOfOfficeFormReducer implements IFormReducer {
    private HttpRequest $request;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     */
    public function __construct(HttpRequest $request) {
        $this->request = $request;
    }

    public function applyReducer(FormStateList &$stateList) {
        if(!$this->request->isAjax) {
            return;
        }

        if($stateList->dateFrom !== null && $stateList->dateFrom->value !== null) {
            if(strtotime($stateList->dateFrom->value) < strtotime(date('Y-m-d'))) {
                $stateList->dateFrom->value = date('Y-m-d');
            }

            if($stateList->dateTo !== null && $stateList->dateTo->value !== null) {
                if($stateList->dateTo->value < $stateList->dateFrom->value) {
                    $stateList->dateTo->value = $stateList->dateFrom->value;
                }
            }
        }
    }
}

?>