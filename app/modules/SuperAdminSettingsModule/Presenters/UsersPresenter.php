<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\GridHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class UsersPresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('UsersPresenter', 'Users');
    }

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('New user', $this->createURL('newUserForm'), 'link')
        ];
    }

    protected function createComponentUsersGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->userRepository->composeQueryForUsers(), 'userId');
        $grid->setGridName(GridHelper::GRID_USERS);

        $grid->addColumnText('fullname', 'Full name');
        $grid->addColumnText('username', 'Username');

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if(!in_array($row->username, ['admin', 'service_user', $this->getUser()->getUsername()])) {
                return true;
            } else {
                return false;
            }
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                ->text('Delete')
                ->class('grid-link')
                ->href($this->createFullURLString('SuperAdminSettings:UsersSettings', 'deleteUser', ['userId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleNewUserForm(?FormResponse $fr = null) {
        if($fr !== null) {
            try {
                if($fr->password != $fr->password2) {
                    throw new GeneralException('Passwords do not match.', null, false);
                }

                $ok = true;
                try {
                    $user = $this->app->userManager->getUserByUsername($fr->username);
                    $ok = false;
                } catch(AException) {}

                if($ok === false) {
                    throw new GeneralException('User with this username already exists.', null, false);
                }

                $this->app->userRepository->beginTransaction(__METHOD__);

                $email = null;
                if(isset($fr->email)) {
                    $email = $fr->email;
                }

                $this->app->userManager->createNewUser($fr->username, $fr->fullname, password_hash($fr->password, PASSWORD_BCRYPT), $email);

                $this->app->userRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User successfully created.', 'success');
            } catch(AException $e) {
                $this->app->userRepository->rollback(__METHOD__);

                $this->flashMessage('Could not create user. Reason: ' . $e->getMessage(), 'error', 10);
            }

            $this->redirect($this->createURL('list'));
        } else {
            $form = new FormBuilder();

            $form->setMethod()
                ->setAction($this->createURL('newUserForm'))
                ->addTextInput('username', 'Username:', null, true)
                ->addTextInput('fullname', 'Full name:', null, true)
                ->addEmailInput('email', 'Email:')
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('password2', 'Password again:', null, true)
                ->addSubmit('Submit')
            ;

            $this->saveToPresenterCache('form', $form);
        }
    }

    public function renderNewUserForm() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'link')
        ];

        $this->template->form = $this->loadFromPresenterCache('form');
    }
}

?>