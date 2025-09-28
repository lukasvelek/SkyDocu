<?php

namespace App\Components\UserInOrganizationChart;

use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\UI\AComponent;

class UserInOrganizationChart extends AComponent {
    private string $userId;

    public function __construct(
        HttpRequest $request
    ) {
        parent::__construct($request);
    }

    /**
     * Sets user ID
     * 
     * @param string $userId User ID
     */
    public function setUserId(string $userId) {
        $this->userId = $userId;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $template->chart_steps = $this->getLoadingAnimationScript(
            'getData',
            'chart-steps',
            'grid',
            [
                'userId' => $this->userId
            ]
        );

        return $template->render()->getRenderedContent();
    }

    public function actionGetData() {
        return new JsonResponse([
            'grid' => $this->build()
        ]);
    }

    /**
     * Processes the organization chart
     */
    private function build(): string {
        $tiles = $this->getUserWithSuperiors();

        $code = implode('<div id="center" style="font-size: 24px">&darr;</div>', $tiles);

        return $code;
    }

    /**
     * Returns the user with their superiors
     */
    private function getUserWithSuperiors(): array {
        $users = [];
        $this->getUsersSuperiorRecursively($this->userId, $users);

        $tiles = [];

        /**
         * @var \App\Entities\UserEntity $user
         */
        foreach($users as $user) {
            $fullname = $user->getFullname();

            $tileTemplate = $this->getTemplate(__DIR__ . '\\chart-step-template.html');

            $info = '
                <b class="page-text">' . $fullname . '</b>
            ';

            $tileTemplate->step_info = $info;

            $tiles[] = $tileTemplate->render()->getRenderedContent();
        }

        return $tiles;
    }

    /**
     * Returns users superior recursively
     * 
     * @param string $userId User ID
     * @param array &$users Users array
     */
    private function getUsersSuperiorRecursively(string $userId, array &$users) {
        $user = $this->app->userManager->getUserById($userId);

        array_unshift($users, $user);

        if($user->getSuperiorUserId() !== null) {
            $this->getUsersSuperiorRecursively($user->getSuperiorUserId(), $users);
        }
    }

    public static function createFromComponent(AComponent $component) {}
}