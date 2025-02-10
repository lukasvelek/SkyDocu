<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\InvoiceCurrencies;
use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Managers\Container\StandaloneProcessManager;
use App\UI\AComponent;
use App\UI\FormBuilder2\SelectOption;

/**
 * Invoice represents the Invoice standalone process
 * 
 * @author Lukas Velek
 */
class Invoice extends AProcessForm {
    private const COMPANY_LIMIT_FOR_SEARCH = 5;

    private StandaloneProcessManager $standaloneProcessManager;
    private array $mCompaniesCache;
    private bool $isCompanySearch;

    public function __construct(HttpRequest $request, StandaloneProcessManager $standaloneProcessManager) {
        parent::__construct($request);

        $this->standaloneProcessManager = $standaloneProcessManager;
        $this->mCompaniesCache = [];
        $this->isCompanySearch = false;
    }

    public function startup() {
        if(count($this->getCompaniesOptionsForSelect()) >= self::COMPANY_LIMIT_FOR_SEARCH) {
            // searching
            $this->isCompanySearch = true;

            $par = new PostAjaxRequest($this->httpRequest);
            $par->setComponentUrl($this, 'searchCompanies');
            $par->setData(['query' => '_query', 'name' => 'invoice']);
            $par->addArgument('_query');
            
            $updateOperation = new HTMLPageOperation();
            $updateOperation->setHtmlEntityId('company')
                ->setJsonResponseObjectName('companies');

            $par->addOnFinishOperation($updateOperation);

            $this->addScript($par);

            $this->addScript('
                async function searchCompanies() {
                    const query = $("#companySearch").val();

                    await ' . $par->getFunctionName() . '(query);
                }
            ');
        }

        parent::startup();
    }

    protected function createForm() {
        $this->addTextInput('invoiceNo', 'Invoice No.:')
            ->setRequired();

        if($this->isCompanySearch) {
            $this->addTextInput('companySearch', 'Search companies:')
                ->setRequired();

            $this->addButton('Search')
                ->setOnClick('searchCompanies()');
        }

        $select = $this->addSelect('company', 'Company:')
            ->setRequired();

        if(!$this->isCompanySearch) {
            $select->addRawOptions($this->getCompaniesOptionsForSelect());
        }

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

    private function getCompaniesOptionsForSelect(?string $query = null) {
        if(empty($this->mCompaniesCache) || $query !== null) {
            $values = $this->standaloneProcessManager->getProcessMetadataEnumValues('invoice', 'companies', $query);

            $options = [];
            foreach($values as $value) {
                $options[] = [
                    'value' => $value->metadataKey,
                    'text' => $value->title
                ];
            }

            if($query !== null) {
                $tmp = [];
                foreach($options as $option) {
                    $so = new SelectOption($option['value'], $option['text']);
                    $tmp[] = $so->render();
                }

                return implode('', $tmp);
            }

            $this->mCompaniesCache = $options;
        }

        return $this->mCompaniesCache;
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::INVOICE;

        $this->setAction($url);
    }

    public static function createFromComponent(AComponent $component) {}

    public function actionSearchCompanies() {
        $query = $this->httpRequest->get('query');

        $companies = $this->getCompaniesOptionsForSelect($query);

        return new JsonResponse(['companies' => $companies]);
    }
}

?>