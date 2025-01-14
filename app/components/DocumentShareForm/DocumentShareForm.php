<?php

namespace App\Components\DocumetnShareForm;

use App\Core\AjaxRequestBuilder;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Repositories\UserRepository;
use App\UI\FormBuilder2\FormBuilder2;

/**
 * DocumentShareForm is a form used for sharing documents
 * 
 * @author Lukas Velek
 */
class DocumentShareForm extends FormBuilder2 {
    private UserRepository $userRepository;

    public function __construct(HttpRequest $request, UserRepository $userRepository) {
        parent::__construct($request);
        $this->userRepository = $userRepository;

        $this->componentName = 'DocumentShareForm';
    }

    public function render() {
        $this->beforeRender();

        return parent::render();
    }

    /**
     * Processes necessary operations before rendering
     */
    private function beforeRender() {
        $this->createForm();
        $this->createScripts();
    }

    /**
     * Creates the form elements
     */
    private function createForm() {
        $this->addTextInput('userSearch', 'Search user:')
            ->setRequired();

        $this->addButton('Search')
            ->setOnClick('searchUsers()');

        $this->addSelect('user', 'User:')
            ->setDisabled();

        $this->addSubmit('Share');
    }

    /**
     * Creates JS scripts
     */
    private function createScripts() {
        $arb = new AjaxRequestBuilder();
        
        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-searchUsers')
            ->setHeader(['query' => '_query'])
            ->setFunctionName('getUsers')
            ->setFunctionArguments(['_query', '_lastContainer'])
            ->updateHTMLElement('user', 'users');

        $this->addScript($arb->build());
        
        // Search button handler
        $code = 'function searchUsers() {
            var _query = $("#userSearch").val();

            if(_query.length == 0) {
                alert("No username entered.");
            } else {
                getUsers(_query);
                $("#user").removeAttr("disabled");
            }
        }';

        $this->addScript($code);
    }

    protected function actionSearchUsers() {
        $query = $this->httpRequest->query['query'];

        $usersDb = $this->getUsers($query);

        $users = [];
        foreach($usersDb as $userDb) {
            $users[] = '<option value="' . $userDb->getId() . '">' . $userDb->getFullname() . '</option>';
        }

        return new JsonResponse(['users' => implode('', $users)]);
    }

    /**
     * Returns found users
     * 
     * @param string $query Username
     * @return array Found users
     */
    private function getUsers(string $query) {
        return $this->userRepository->searchUsersByUsername($query, [$this->app->currentUser->getId()]);
    }
}

?>