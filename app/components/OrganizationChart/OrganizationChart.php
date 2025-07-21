<?php

namespace App\Components\OrganizationChart;

use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Exceptions\GeneralException;
use App\UI\AComponent;

class OrganizationChart extends AComponent {
    private ?string $userId;
    private string $containerId;

    public function __construct(
        HttpRequest $request
    ) {
        parent::__construct($request);
    }

    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

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

    private function build(): string {
        $tiles = $this->getUserWithSubordinates();

        $code = implode('', $tiles);

        return $code;
    }

    private function getUserWithSubordinates() {
        $users = $this->getUserSubordinates();

        if($this->userId !== null) {
            $selectedUser = $this->app->userManager->getUserById($this->userId);

            array_unshift($users, $selectedUser);
        }

        $tiles = [];

        /**
         * @var \App\Entities\UserEntity $user
         */
        foreach($users as $user) {
            $fullname = $user->getFullname();

            $tileTemplate = $this->getTemplate(__DIR__ . '\\chart-step-template.html');

            if($user->getId() == $this->app->currentUser->getId()) {
                $info = '<b class="page-text">' . $fullname . '</b>';
            } else {
                $info = '<p class="page-text">' . $fullname . '</p>';
            }

            $tileTemplate->step_info = $info;

            $tiles[] = $tileTemplate->render()->getRenderedContent();
        }

        return $tiles;
    }

    private function getUserSubordinates() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $groupUsers = $this->app->groupManager->getGroupUsersForGroupTitle($container->getTitle() . ' - users');

        $qb = $this->app->userRepository->composeQueryForUsers();

        if($this->userId === null) {
            $qb->andWhere('superiorUserId IS NULL');
        } else {
            $qb->andWhere('superiorUserId = ?', [$this->userId]);
        }

        $qb->andWhere($qb->getColumnInValues('userId', $groupUsers))
            ->orderBy('fullname')
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = $this->app->userManager->getUserById($row['userId']);
        }

        return $users;
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