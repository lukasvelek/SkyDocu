<?php

namespace App\Components\OrganizationChart;

use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\UI\AComponent;

class OrganizationChart extends AComponent {
    private ?string $userId;
    private string $containerId;
    private array $mUserCache;

    public function __construct(
        HttpRequest $request
    ) {
        parent::__construct($request);

        $this->mUserCache = [];
    }

    /**
     * Sets container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    /**
     * Sets user ID
     * 
     * @param ?string $userId User ID
     */
    public function setUserId(?string $userId) {
        $this->userId = $userId;
    }

    public function render() {
        if(!isset($this->containerId)) {
            throw new GeneralException('No container ID is set.');
        }

        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $template->chart_steps = $this->getLoadingAnimationScript();

        return $template->render()->getRenderedContent();
    }

    public function actionGetData() {
        return new JsonResponse([
            'grid' => $this->build()
        ]);
    }

    /**
     * Builds the component
     */
    private function build(): string {
        $tiles = $this->getTiles();

        $code = '';
        foreach($tiles as $tile) {
            if($tile == '<div id="center" style="font-size: 24px">&darr;</div>') {
                $code .= $tile;
            } else {
                $code .= $tile;
            } 
        }

        return $code;
    }

    /**
     * Returns tiles
     */
    private function getTiles(): array {
        if($this->userId === null) {
            // root
            return $this->getRootTiles();
        } else {
            // not root
            return $this->getNotRootTiles();
        }
    }

    /**
     * Returns an array of tiles for non-root view
     */
    private function getNotRootTiles(): array {
        $tiles = [];

        $currentUser = $this->getUserById($this->userId);

        // superior user to the currently selected
        $superiorUserId = $currentUser->getSuperiorUserId();

        if($superiorUserId !== null) {
            $superiorUser = $this->getUserById($superiorUserId);

            $tiles[] = $this->getFillTile('<p class="page-text">' . $superiorUser->getFullname() . '</p>', $superiorUserId);

            $tiles[] = $this->getDownArray();
        }

        // currently selected user
        $tiles[] = $this->getFillTile('<p class="page-text"><b>' . $currentUser->getFullName() . '</b></p>', $this->userId, true);

        // subordinates
        $subordinates = $this->getSubordinatesForUser($currentUser->getId());

        if(!empty($subordinates)) {
            $tiles[] = $this->getDownArray();
        }

        $userTiles = [];
        foreach($subordinates as $user) {
            $userTiles[] = $this->getFillTile('<p class="page-text">' . $user->getFullname() . '</p>', $user->getId());
        }

        $tiles[] = implode('<br>', $userTiles);

        return $tiles;
    }

    /**
     * Returns an array of tiles for root view
     */
    private function getRootTiles(): array {
        $tiles = [];

        // root user
        $rootUser = $this->getTopUser();
    
        $tiles[] = $this->getFillTile('<p class="page-text">' . $rootUser->getFullname() . '</p>', $rootUser->getId(), true);

        // subordinates
        $subordinates = $this->getSubordinatesForUser($rootUser->getId());

        if(!empty($subordinates)) {
            $tiles[] = $this->getDownArray();
        }

        $userTiles = [];
        foreach($subordinates as $user) {
            $userTiles[] = $this->getFillTile('<p class="page-text">' . $user->getFullname() . '</p>', $user->getId());
        }

        $tiles[] = implode('<br>', $userTiles);

        return $tiles;
    }

    /**
     * Returns HTML code for a down array
     */
    private function getDownArray(): string {
        return '<div id="center" style="font-size: 24px">&darr;</div>';
    }

    /**
     * Fills a new tile template
     * 
     * @param string $text Text
     * @param string $userId User ID
     * @param bool $isRoot Is root view?
     */
    private function getFillTile(string $text, string $userId, bool $isRoot = false): string {
        $tileTemplate = $this->getTemplate(__DIR__ . '\\chart-step-template.html');

        if($userId == $this->app->currentUser->getId()) {
            $text = sprintf('<u>%s</u>', $text);
        }
        if($userId != $this->userId && !$isRoot) {
            $text = '<a class="link" href="' . $this->createUserLink($userId) . '">' . $text . '</a>';
        }

        $tileTemplate->step_info = $text;

        return $tileTemplate->render()->getRenderedContent();
    }

    /**
     * Returns a link for given user
     * 
     * @param string $userId User ID
     */
    private function createUserLink(string $userId) {
        $params = [];
        
        $topUser = $this->getTopUser();
        if($topUser->getId() != $userId) {
            $params['userId'] = $userId;
        }

        return $this->createFullURLString(
            $this->presenter->moduleName . ':' . substr($this->presenter->name, 0, strlen($this->presenter->name) - strlen('Presenter')),
            $this->presenter->getAction(),
            $params
        );
    }

    /**
     * Returns an array of subordinates
     * 
     * @param string $userId User ID
     */
    private function getSubordinatesForUser(string $userId): array {
        $qb = $this->app->userRepository->composeQueryForUsers();

        $qb->andWhere('superiorUserId = ?', [$userId])
            ->andWhere($qb->getColumnInValues('userId', $this->getUsersInContainerArray()))
            ->orderBy('fullname')
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = UserEntity::createEntityFromDbRow($row);
        }

        return $users;
    }

    /**
     * Returns top user
     */
    private function getTopUser(): UserEntity {
        $qb = $this->app->userRepository->composeQueryForUsers();

        $qb->andWhere('superiorUserId IS NULL')
            ->andWhere($qb->getColumnInValues('userId', $this->getUsersInContainerArray()))
            ->orderBy('fullname')
            ->limit(1)
            ->execute();

        return $this->getUserById($qb->fetch('userId'));
    }

    /**
     * Returns an array of user IDs of users in containers
     */
    private function getUsersInContainerArray(): array {
        $container = $this->app->containerManager->getContainerById($this->containerId);
        
        return $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');
    }

    /**
     * Returns user by ID
     * 
     * @param string $userId User ID
     */
    private function getUserById(string $userId): UserEntity {
        if(!array_key_exists($userId, $this->mUserCache)) {
            $user = $this->app->userManager->getUserById($userId, true);

            $this->mUserCache[$userId] = $user;   
        }

        return $this->mUserCache[$userId];
    }

    /**
     * Returns loading animation script
     */
    private function getLoadingAnimationScript(): string {
        $par = new PostAjaxRequest($this->httpRequest);

        $par->setComponentUrl($this, 'getData');

        if($this->userId !== null) {
            $par->addUrlParameter('userId', $this->userId);
        }
        
        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('chart-steps')
            ->setJsonResponseObjectName('grid');

        $par->addOnFinishOperation($updateOperation);

        $script = '
            <div id="center">
                <img src="resources/loading.gif" width="64px" height="64px">
                <br>
                Loading...
            </div>
            <script type="text/javascript">
                ' . $par->build() . '

                ' . $par->getFunctionName() . '();
            </script>
        ';

        return $script;
    }

    public static function createFromComponent(AComponent $component) {}
}