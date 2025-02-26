<?php

namespace App\Components\ProcessForm\Processes\Reducers;

use App\Core\Http\HttpRequest;
use App\UI\FormBuilder2\FormState\FormStateList;
use App\UI\FormBuilder2\IFormReducer;

/**
 * Default reducer for the HomeOffice process form
 * 
 * @author LUkas Velek
 */
class HomeOfficeReducer implements IFormReducer {
    private HttpRequest $request;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instnace
     */
    public function __construct(HttpRequest $request) {
        $this->request = $request;
    }

    public function applyReducer(FormStateList &$stateList) {
        if(!$this->request->isAjax) {
            return;
        }

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
}

?>