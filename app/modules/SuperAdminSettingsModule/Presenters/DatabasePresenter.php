<?php

namespace App\Modules\SuperAdminSettingsModule;

use App\Constants\ContainerStatus;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseMigrationManager;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Core\Http\FormRequest;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\HTML\HTML;

class DatabasePresenter extends ASuperAdminSettingsPresenter {
    public function __construct() {
        parent::__construct('DatabasePresenter', 'Database');
    }

    public function renderHome() {
        $migration = FileManager::loadFile(APP_ABSOLUTE_DIR . 'app\\core\\migration');
        $migrationParts = explode('_', $migration);
        $schema = $migrationParts[2];
        $date = $migrationParts[1];

        $date = DateTimeFormatHelper::formatDateToUserFriendly($date, $this->app->currentUser->getDateFormat());

        $this->template->current_db_schema = (int)$schema . " ($date)";

        $dmm = new DatabaseMigrationManager($this->app->systemServicesRepository->conn, null, $this->logger);
        $migrations = $dmm->getAvailableMigrations();
        $dmm->filterOnlyUpstreamMigrations($migrations);
        
        $lastMigration = '-';
        $hasNewer = false;
        if(count($migrations) > 0) {
            $lastMigration = $migrations[count($migrations) - 1];
            $lastMigration = FileManager::getFilenameFromPath($lastMigration);
            $lastMigrationParts = explode('_', $lastMigration);
            $schema = $lastMigrationParts[2];
            $date = $lastMigrationParts[1];

            $date = DateTimeFormatHelper::formatDateToUserFriendly($date, $this->app->currentUser->getDateFormat());

            $lastMigration = (int)$schema . " ($date)";
            $hasNewer = true;
        }

        $this->template->available_db_schema = $lastMigration;

        if($hasNewer) {
            $el = HTML::el('a');
            $el->text('Run migrations')
                ->class('link')
                ->style('color', 'red')
                ->href($this->createURLString('runMigrationsForm'));

            $this->template->db_schema_update_link = $el->toString();
        } else {
            $this->template->db_schema_update_link = '';
        }

        $containers = $this->app->containerManager->getContainersInDistribution();

        $this->template->containers_in_distribution = count($containers);
        if(count($containers) > 0) {
            $date = DatabaseMigrationManager::getMigrationReleaseDateFromNumber($containers[0]->getDefaultDatabase()->getDbSchema(), true);
            $date = DateTimeFormatHelper::formatDateToUserFriendly($date, $this->app->currentUser->getDateFormat());

            $this->template->current_db_schema_in_distribution = $containers[0]->getDefaultDatabase()->getDbSchema() . ' (' . $date . ')';

            $dmm = new DatabaseMigrationManager($this->app->systemServicesRepository->conn, null, $this->logger);
            $dmm->setContainer($containers[0]->getId());

            $migrations = $dmm->getAvailableMigrations();
            $dmm->filterOnlyUpstreamMigrations($migrations);

            $lastMigration = '-';
            $hasNewer = false;
            if(count($migrations) > 0) {
                $lastMigration = $migrations[count($migrations) - 1];

                if($lastMigration !== null) {
                    $lastMigration = FileManager::getFilenameFromPath($lastMigration);
                    $lastMigrationParts = explode('_', $lastMigration);
                    $schema = $lastMigrationParts[2];
                    $date = $lastMigrationParts[1];
    
                    $date = DateTimeFormatHelper::formatDateToUserFriendly($date, $this->app->currentUser->getDateFormat());
    
                    $lastMigration = (int)$schema . " ($date)";
                    $hasNewer = true;
                }
            }

            $this->template->available_db_schema_in_distribution = $lastMigration;

            if($hasNewer) {
                $el = HTML::el('a');
                $el->text('Run migrations for distribution')
                    ->class('link')
                    ->style('color', 'red')
                    ->href($this->createURLString('runMigrationsForm', ['isDistribution' => '1']));

                $this->template->distrib_db_schema_update_link = $el->toString();
            } else {
                $this->template->distrib_db_schema_update_link = '';
            }

            $allContainers = $this->app->containerManager->getAllContainers(false, true);
            $this->template->containers_total = count($allContainers);
        } else {
            $this->template->current_db_schema_in_distribution = '-';
            $this->template->available_db_schema_in_distribution = '-';
            $this->template->distrib_db_schema_update_link = '';
            $this->template->containers_total = 0;
        }
    }

    public function handleRunMigrationsForm(?FormRequest $fr = null) {
        if($fr !== null) {
            $_hash = $this->httpRequest->get('h');

            try {
                if($_hash !== md5($fr->hash)) {
                    throw new GeneralException('Entered verification code does not match the one provided by the system.');
                }

                $this->app->userAuth->authUser($fr->password);

                $url = $this->createURL('runMigrations');

                if($this->httpRequest->get('isDistribution') == '1') {
                    $url = $this->createURL('runMigrationsForDistribution');
                }

                $this->redirect($url);
            } catch(AException $e) {
                $this->flashMessage('An error occured during processing your request. Reason: ' . $e->getMessage(), 'error', 10);

                $this->redirect($this->createURL('home'));
            }
        }
    }

    public function renderRunMigrationsForm() {}

    protected function createComponentRunMigrationsForm(HttpRequest $request) {
        $form = $this->componentFactory->getFormBuilder();

        $hash = HashManager::createHash(8);

        $form->setAction($this->createURL('runMigrationsForm', ['h' => md5($hash), 'isDistribution' => ($request->get('isDistribution') == '1' ? '1' : '0')]));

        $form->addLabel('lbl_text1', 'Are you sure you want to run the migrations now?');

        if($request->get('isDistribution') == '1') {
            $form->addLabel('lbl_text2', 'Running them will temporarily disable all currently running containers that are in distribution, run the migrations and finally enable all containers that had been disabled before.');
        } else {
            $form->addLabel('lbl_text2', 'Running them will temporarily disable all currently running containers, run the migrations and finally enable all containers that had been disabled before.');
        }

        $form->addLabel('lbl_text3', 'If you are sure, please enter your password below to be authenticated.');

        $form->addPasswordInput('password', 'Your password:')
            ->setRequired();

        $form->addLabel('lbl_text4', 'Please enter the verification code below into the respective input field:');
        $form->addLabel('lbl_text5', 'Generated hash: <b>' . $hash . '</b>');

        $form->addTextInput('hash', 'Verification code:')
            ->setRequired();

        $form->addSubmit('Run migrations');
        $form->addButton('Go back')
            ->setOnClick('location.href = \'' . $this->createURLString('home') . '\';');

        return $form;
    }

    public function handleRunMigrationsForDistribution() {
        try {
            // get all containers in distribution
            $containersInDistribution = $this->app->containerManager->getContainersInDistribution();

            // get enabled containers
            $qb = $this->app->containerRepository->composeQueryForContainers();
            $qb->andWhere('status = ?', [ContainerStatus::RUNNING]);
            $qb->execute();

            $enabledContainers = [];
            while($row = $qb->fetchAssoc()) {
                $enabledContainers[] = $row['containerId'];
            }

            // disable them
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);
                foreach($enabledContainers as $containerId) {
                    $this->app->containerManager->changeContainerStatus($containerId, ContainerStatus::NOT_RUNNING, $this->app->serviceManager->getServiceUserId(), 'Status change due to migrations. Container was disabled by ' . $this->getUser()->getFullname() . ' (ID: ' . $this->getUserId() . ').');
                }
                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);
                throw $e;
            }

            // run migrations
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);

                foreach($containersInDistribution as $container) {
                    $this->app->containerManager->runContainerDatabaseMigrations($container->getDefaultDatabase()->getName(), $container->getId());
                }

                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);
                throw $e;
            }

            // enable before-enabled containers
            try {
                $this->app->containerRepository->beginTransaction(__METHOD__);
                foreach($enabledContainers as $containerId) {
                    $this->app->containerManager->changeContainerStatus($containerId, ContainerStatus::RUNNING, $this->app->serviceManager->getServiceUserId(), 'Status change due to migrations. Container was enabled by ' . $this->getUser()->getFullname() . ' (ID: ' . $this->getUserId() . ').');
                }
                $this->app->containerRepository->commit($this->getUserId(), __METHOD__);
            } catch(AException $e) {
                $this->app->containerRepository->rollback(__METHOD__);
                throw $e;
            }

            // invalidate cache
            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINERS);
            $this->cacheFactory->invalidateCacheByNamespace(CacheNames::CONTAINER_DATABASES);

            // return
            $this->flashMessage('Migrations ran successfully.', 'success');
        } catch(AException $e) {
            $this->flashMessage('An error occurred during migrations. Error: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('home'));
    }

    public function handleRunMigrations() {
        try {
            // get enabled containers
            $qb = $this->app->containerRepository->composeQueryForContainers();
            $qb->andWhere('status = ?', [ContainerStatus::RUNNING]);
            $qb->execute();

            $enabledContainers = [];
            while($row = $qb->fetchAssoc()) {
                $enabledContainers[] = $row['containerId'];
            }

            // disable them
            $this->app->containerRepository->beginTransaction(__METHOD__);
            foreach($enabledContainers as $containerId) {
                $this->app->containerManager->changeContainerStatus($containerId, ContainerStatus::NOT_RUNNING, $this->app->serviceManager->getServiceUserId(), 'Status change due to migrations. Container was disabled by ' . $this->getUser()->getFullname() . ' (ID: ' . $this->getUserId() . ').');
            }
            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            // run migrations
            $this->app->containerRepository->beginTransaction(__METHOD__);
            $dmm = new DatabaseMigrationManager($this->app->containerRepository->conn, null, $this->logger);
            $dmm->runMigrations(true);
            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            // enable before-enabled containers
            $this->app->containerRepository->beginTransaction(__METHOD__);
            foreach($enabledContainers as $containerId) {
                $this->app->containerManager->changeContainerStatus($containerId, ContainerStatus::RUNNING, $this->app->serviceManager->getServiceUserId(), 'Status change due to migrations. Container was enabled by ' . $this->getUser()->getFullname() . ' (ID: ' . $this->getUserId() . ').');
            }
            $this->app->containerRepository->commit($this->getUserId(), __METHOD__);

            // return
            $this->flashMessage('Migrations ran successfully.', 'success');
        } catch(AException $e) {
            $this->app->containerRepository->rollback(__METHOD__);

            $this->flashMessage('An error occurred during migrations. Error: ' . $e->getMessage(), 'error', 10);
        }

        $this->redirect($this->createURL('home'));
    }
}

?>