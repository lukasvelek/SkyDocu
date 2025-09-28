<?php

namespace App\Components\UserSubstituteForm;

use App\Core\Http\Ajax\Operations\CustomOperation;
use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Repositories\UserRepository;
use App\UI\FormBuilder2\FormBuilder2;
use App\UI\FormBuilder2\SelectOption;

/**
 * UserSubstituteForm is used for user substitute selection
 * 
 * @author Lukas Velek
 */
class UserSubstituteForm extends FormBuilder2 {
    private UserRepository $userRepository;

    private string $currentUserId;

    public function __construct(HttpRequest $request, UserRepository $userRepository) {
        parent::__construct($request);
        $this->userRepository = $userRepository;

        $this->componentName = 'UserSubstituteForm';
    }

    /**
     * Sets current user's ID
     * 
     * @param string $currentUserId
     */
    public function setCurrentUserId(string $currentUserId) {
        $this->currentUserId = $currentUserId;
    }

    public function render() {
        $this->beforeRender();

        return parent::render();
    }

    /**
     * Sets up the form
     */
    private function beforeRender() {
        $this->createForm();
        $this->createScripts();
    }

    /**
     * Adds the form elements
     */
    private function createForm() {
        $this->addTextInput('userSearch', 'Search user:')
            ->setRequired();

        $this->addButton('Search')
            ->setOnClick('searchUsers()');

        $this->addSelect('user', 'User:')
            ->setDisabled();

        $this->addSubmit()
            ->setDisabled();
    }

    /**
     * Creates the JS scripts
     */
    private function createScripts() {
        $par = new PostAjaxRequest($this->httpRequest);

        $par->setComponentUrl($this, 'searchUsers')
            ->setData(['query' => '_query'])
            ->addArgument('_query');

        $usersOperation = new HTMLPageOperation();
        $usersOperation->setHtmlEntityId('user')
            ->setJsonResponseObjectName('users');

        $formOperation = new CustomOperation();
        $formOperation->addCode('if(obj.users.length == 0) {
            alert("No users found.");
            $("#user").attr("disabled");
            $("#formSubmit").attr("disabled");
        } else {
            ' . $usersOperation->build() . '
            $("#user").removeAttr("disabled");
            $("#formSubmit").removeAttr("disabled");
        }');

        $par->addOnFinishOperation($formOperation);

        $this->addScript($par);

        $code = 'function searchUsers() {
            var _query = $("#userSearch").val();

            if(_query.length == 0) {
                alert("No username entered.");
            } else {
                ' . $par->getFunctionName() . '(_query);
            }
        };';

        $this->addScript($code);
    }

    public function actionSearchUsers() {
        $query = $this->httpRequest->get('query');

        $usersDb = $this->userRepository->searchUsers($query, ['email', 'fullname'], [$this->currentUserId]);

        $users = [];
        foreach($usersDb as $user) {
            $_ = new SelectOption($user->getId(), $user->getFullname());

            $users[] = $_->render();
        }

        return new JsonResponse(['users' => implode('', $users)]);
    }
}

?>