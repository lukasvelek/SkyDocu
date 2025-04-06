<?php

namespace App\Components\ProcessForm\Processes;

use App\Constants\Container\StandaloneProcesses;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Managers\Container\StandaloneProcessManager;
use App\Managers\UserManager;
use App\Repositories\Container\PropertyItemsRepository;
use App\UI\AComponent;
use App\UI\FormBuilder2\SelectOption;

class PropertyMoveRequest extends AProcessForm {
    private UserManager $userManager;
    private StandaloneProcessManager $standaloneProcessManager;
    private PropertyItemsRepository $propertyItemsRepository;

    public function __construct(HttpRequest $request, UserManager $userManager, StandaloneProcessManager $standaloneProcessManager) {
        parent::__construct($request);

        $this->userManager = $userManager;
        $this->standaloneProcessManager = $standaloneProcessManager;

        $this->propertyItemsRepository = new PropertyItemsRepository($this->standaloneProcessManager->processManager->processRepository->conn, $this->standaloneProcessManager->processManager->processRepository->getLogger());
    }

    public function startup() {
        $par = new PostAjaxRequest($this->httpRequest);
        $par->setComponentUrl($this, 'searchUsers');
        $par->setData(['query' => '_query', 'name' => 'requestPropertyMove']);
        $par->addArgument('_query');

        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('user')
            ->setJsonResponseObjectName('users');

        $par->addOnFinishOperation($updateOperation);

        $this->addScript($par);

        $this->addScript('
            async function searchUsers() {
                const query = $("#userSearch").val();

                await ' . $par->getFunctionName() . '(query);
            }
        ');

        parent::startup();
    }

    protected function createForm() {
        $this->addSelect('item', 'Property item:')
            ->setRequired()
            ->addRawOptions($this->getMyPropertyItems());
        
        $this->addTextInput('userSearch', 'Search users:')
            ->setRequired();

        $this->addButton('Seach users')
            ->setOnClick('searchUsers()');

        $this->addSelect('user', 'User:')
            ->setRequired();

        $this->addSubmit('Request');
    }

    protected function createAction() {
        $url = $this->baseUrl;
        $url['name'] = StandaloneProcesses::REQUEST_PROPERTY_MOVE;

        $this->setAction($url);
    }

    public static function createFromComponent(AComponent $component) {}
    
    private function getMyPropertyItems(): array {
        $myItems = $this->propertyItemsRepository->getPropertyItemsForUser($this->app->currentUser->getId());

        $myItemIds = [];
        foreach($myItems as $item) {
            $myItemIds[] = $item['itemId'];
        }

        $allItems = $this->standaloneProcessManager->getProcessMetadataEnumValues(StandaloneProcesses::REQUEST_PROPERTY_MOVE, 'items');

        $items = [];
        foreach($allItems as $item) {
            if(in_array($item->valueId, $myItemIds)) {
                $items[] = [
                    'value' => $item->title2,
                    'text' => $item->title . ' (' . $item->title2 . ')'
                ];
            }
        }

        return $items;
    }

    public function actionSearchItems() {
        $query = $this->httpRequest->get('query');

        $values = $this->standaloneProcessManager->getProcessMetadataEnumValues(StandaloneProcesses::REQUEST_PROPERTY_MOVE, 'items', $query);

        $options = [];
        foreach($values as $value) {
            $so = new SelectOption($value->metadataKey, $value->title . ' (' . $value->title2 . ')');
            $options[] = $so->render();
        }

        return new JsonResponse(['items' => implode('', $options)]);
    }

    public function actionSearchUsers() {
        $query = $this->httpRequest->get('query');

        $users = $this->userManager->searchUsersByUsernameAndFullname($query, [$this->app->currentUser->getId()]);

        $selectUsers = [];
        foreach($users as $userId => $fullname) {
            $so = new SelectOption($userId, $fullname);
            $selectUsers[] = $so->render();
        }

        return new JsonResponse(['users' => implode('', $selectUsers)]);
    }
}

?>