<?php

namespace App\Components\DocumetnShareForm;

use App\Core\AjaxRequestBuilder;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Managers\Container\DocumentManager;
use App\Repositories\UserRepository;
use App\UI\FormBuilder2\FormBuilder2;

/**
 * DocumentShareForm is a form used for sharing documents
 * 
 * @author Lukas Velek
 */
class DocumentShareForm extends FormBuilder2 {
    private UserRepository $userRepository;
    private DocumentManager $documentManager;
    private array $documentIds;

    public function __construct(HttpRequest $request, UserRepository $userRepository, DocumentManager $documentManager) {
        parent::__construct($request);
        $this->userRepository = $userRepository;
        $this->documentManager = $documentManager;

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
        $this->processDocumentIds();
    }

    /**
     * Processes document IDs
     */
    private function processDocumentIds() {
        if(empty($this->documentIds)) {
            return;
        }

        $this->setAdditionalLinkParameters('documentId', $this->documentIds);
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
        
        $arb->setMethod('POST')
            ->setComponentAction($this->presenter, $this->componentName . '-searchUsers')
            ->setFunctionName('getUsers')
            ->setFunctionArguments(['_query', '_lastContainer'])
            ->updateHTMLElement('user', 'users')
            ->setData(['documentIds' => $this->documentIds, 'query' => '_query'])
            ->addWhenDoneOperation('if(obj.usersCount == 0) { alert("No users found."); }');

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
        $query = $this->httpRequest->post['query'];
        $this->documentIds = $this->httpRequest->post['documentIds'];

        $usersDb = $this->getUsers($query);

        $users = [];
        foreach($usersDb as $userDb) {
            $users[] = '<option value="' . $userDb->getId() . '">' . $userDb->getFullname() . '</option>';
        }

        return new JsonResponse(['users' => implode('', $users), 'usersCount' => count($users)]);
    }

    /**
     * Returns found users
     * 
     * @param string $query Username
     * @return array Found users
     */
    private function getUsers(string $query) {
        $usersDb = $this->userRepository->searchUsersByUsername($query, [$this->app->currentUser->getId()]);

        $shares = $this->documentManager->getSharesForDocumentIdsByUserId($this->documentIds, $this->app->currentUser->getId());

        $usersInShares = [];
        foreach($shares as $share) {
            if(!in_array($share->userId, $usersInShares)) {
                $usersInShares[] = $share->userId;
            }
        }
        
        $users = [];
        foreach($usersDb as $user) {
            if(!in_array($user->getId(), $usersInShares)) {
                $users[] = $user;
            }
        }

        return $users;
    }
    
    /**
     * Sets document IDs for sharing
     * 
     * @param array $documentIds Document IDs
     */
    public function setDocumentIds(array $documentIds) {
        $this->documentIds = $documentIds;
    }
}

?>