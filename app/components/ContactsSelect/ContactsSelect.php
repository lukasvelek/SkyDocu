<?php

namespace App\Components\ContactsSelect;

use App\Core\Http\Ajax\Operations\HTMLPageOperation;
use App\Core\Http\Ajax\Requests\PostAjaxRequest;
use App\Core\Http\HttpRequest;
use App\Core\Http\JsonResponse;
use App\Entities\UserEntity;
use App\Helpers\UserHelper;
use App\UI\AComponent;

/**
 * ContactsSelect displays all contacts in the container
 * 
 * @author Lukas Velek
 */
class ContactsSelect extends AComponent {
    private array $users;
    private string $containerId;

    public function __construct(
        HttpRequest $request
    ) {
        parent::__construct($request);
    }

    /**
     * Sets container ID
     * 
     * @param string $containerId Container ID
     */
    public function setContainerId(string $containerId) {
        $this->containerId = $containerId;
    }

    public function render() {
        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $template->contact_tiles = $this->getLoadingAnimationScript();

        return $template->render()->getRenderedContent();
    }

    public function actionGetData() {
        return new JsonResponse([
            'grid' => $this->build()
        ]);
    }

    /**
     * Builds the grid
     */
    private function build(): string {
        $users = $this->getUsers();

        $code = '<div class="row">';

        $maxTilesInRow = 4;

        $row = 0;
        foreach($this->getUserTiles() as $tile) {
            if(($row + 1) < $maxTilesInRow) {
                $code .= '<div class="col-md-3">' . $tile . '</div>';
                $row++;
            } else {
                $row = 0;
                $code .= '</div><div class="row">';
            }
        }

        return $code;
    }

    /**
     * Returns users
     */
    private function getUsers() {
        $container = $this->app->containerManager->getContainerById($this->containerId);

        $groupTitle = $container->getTitle() . ' - users';

        $userIds = $this->app->groupManager->getGroupUsersForGroupTitle($groupTitle);

        $qb = $this->app->userRepository->composeQueryForUsers();

        $qb->andWhere($qb->getColumnInValues('userId', $userIds))
            ->orderBy('fullname')
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = UserEntity::createEntityFromDbRow($row);
        }

        $this->users = $users;
    }

    /**
     * Returns all user tiles
     */
    private function getUserTiles(): array {
        $tiles = [];

        /**
         * @var UserEntity $user
         */
        foreach($this->users as $user) {
            $fullname = $user->getFullname();
            $email = $user->getEmail() ?? '-';

            $tileTemplate = $this->getTemplate(__DIR__ . '\\contact-tile.html');

            $info = '
                <span id="user-' . $user->getId() . '-email"><b>Email: </b> ' . $email . '</span>
            ';

            $tileTemplate->user_fullname = $fullname;
            $tileTemplate->user_info = $info;
            $tileTemplate->user_id = $user->getId();
            $tileTemplate->user_profile_picture_uri = UserHelper::getUserProfilePictureUri(
                $user,
                $this->app->fileStorageManager
            );
            $tileTemplate->user_profile_link = $this->createFullURLString(
                'User:User',
                'profile',
                [
                    'userId' => $user->getId()
                ]
            );

            $tiles[] = $tileTemplate->render()->getRenderedContent();
        }

        return $tiles;
    }

    /**
     * Returns loading animation script
     */
    private function getLoadingAnimationScript() {
        $par = new PostAjaxRequest($this->httpRequest);

        $par->setComponentUrl($this, 'getData');
        
        $updateOperation = new HTMLPageOperation();
        $updateOperation->setHtmlEntityId('contact-tiles')
            ->setJsonResponseObjectName('grid');

        $par->addOnFinishOperation($updateOperation);

        $script = '
            <div id="center">
                <img src="resources/loading.gif" width="64px" height="64px">
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