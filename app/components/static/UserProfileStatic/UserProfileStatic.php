<?php

namespace App\Components\Static\UserProfileStatic;

use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\UserHelper;
use App\Managers\UserAbsenceManager;
use App\Managers\UserManager;
use App\Managers\UserSubstituteManager;
use App\UI\AComponent;
use App\UI\LinkBuilder;

/**
 * UserProfileStatic component is used to display user's profile
 * 
 * @author Lukas Velek
 */
class UserProfileStatic extends AComponent {
    private UserAbsenceManager $userAbsenceManager;
    private UserSubstituteManager $userSubstituteManager;
    private UserManager $userManager;

    private ?UserEntity $user;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request Request
     * @param UserAbsenceManager $userAbsenceManager User absence manager
     * @param UserSubstituteManager $userSubstituteManager User substitute manager
     * @param UserManager $userManager User manager
     */
    public function __construct(
        HttpRequest $request,
        UserAbsenceManager $userAbsenceManager,
        UserSubstituteManager $userSubstituteManager,
        UserManager $userManager
    ) {
        parent::__construct($request);

        $this->userAbsenceManager = $userAbsenceManager;
        $this->userSubstituteManager = $userSubstituteManager;
        $this->userManager = $userManager;

        $this->user = null;
    }

    /**
     * Sets the user
     * 
     * @param UserEntity $user User
     */
    public function setUser(UserEntity $user) {
        $this->user = $user;
    }
    
    public function render() {
        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $template->user_profile = $this->build();
        $template->user_organization_profile = $this->buildOrganization();
        $template->user_profile_picture = $this->getProfilePicture();
        $template->user_profile_picture_change_link = $this->getProfilePictureChangeLink();

        return $template->render()->getRenderedContent();
    }

    /**
     * Builds the profile
     */
    private function build(): string {
        if($this->user === null) {
            throw new GeneralException('No user is set.');
        }

        $code = [];
        $addCode = function(string $title, string $value) use (&$code) {
            $tmp = '<span id="row-' . count($code) . '"><p><b>' . $title . ':</b> ' . $value . '</p></span>';

            $code[] = $tmp;
        };

        $addCode('ID', $this->user->getId());
        $addCode('Fullname', $this->user->getFullname());
        $addCode('Email', $this->user->getEmail() ?? '-');
        $addCode('Member since', $this->getUserMemberSince());
        $addCode('Is absent', $this->getIsUserAbsent());
        $addCode('Substitute', $this->getUserSubstitute());
        $addCode('Superior', $this->getUserSuperior());

        return implode('', $code);
    }

    /**
     * Builds the user organization profile
     */
    private function buildOrganization(): string {
        if($this->user === null) {
            throw new GeneralException('No user is set.');
        }

        $code = [];
        $addCode = function(string $title, ?string $value) use (&$code) {
            if($value === null) {
                $value = '-';
            }
            $tmp = '<span id="row-' . count($code) . '"><p><b>' . $title . ':</b> ' . $value . '</p></span>';

            $code[] = $tmp;
        };

        $addCode('Position', $this->user->getOrgPosition());
        $addCode('Department', $this->user->getOrgDepartment());
        $addCode('Section', $this->user->getOrgSection());
        $addCode('Personal number', $this->user->getPersonalNumber());

        return implode('', $code);
    }

    /**
     * Checks if user is absent and returns Yes if absent or No
     */
    private function getIsUserAbsent(): string {
        $result = $this->userAbsenceManager->getUserCurrentAbsence($this->user->getId());

        return ($result === null) ? 'No' : 'Yes';
    }

    /**
     * Returns formatted date of user creation
     */
    private function getUserMemberSince(): string {
        return DateTimeFormatHelper::formatDateToUserFriendly($this->user->getDateCreated(), $this->app->currentUser->getDatetimeFormat());
    }

    /**
     * Returns user's substitute
     */
    private function getUserSubstitute(): string {
        $substituteRow = $this->userSubstituteManager->getUserSubstitute($this->user->getId());

        if($substituteRow === null) {
            return '-';
        }

        $substitute = $this->userManager->getUserById($substituteRow->subsituteUserId);

        return $substitute->getFullname();
    }

    /**
     * Returns user's superior
     */
    private function getUserSuperior(): string {
        $superiorId = $this->user->getSuperiorUserId();

        if($superiorId === null) {
            return '-';
        }

        $superior = $this->userManager->getUserById($superiorId);

        return $superior->getFullname();
    }

    /**
     * Returns user's profile picture
     */
    private function getProfilePicture(): string {
        $imageSource = UserHelper::getUserProfilePictureUri(
            $this->user,
            $this->app->fileStorageManager
        );

        return '<img src="' . $imageSource . '" width="128px" height="128px" style="border-radius: 100px">';
    }

    /**
     * Returns profile picture change link
     */
    private function getProfilePictureChangeLink(): string {
        if($this->app->currentUser->getId() != $this->user->getId()) {
            return '';
        }

        return LinkBuilder::createSimpleLink(
            'Change profile picture',
            $this->createFullURL(
                sprintf(
                    '%s:%s',
                    $this->presenter->moduleName,
                    substr(
                        $this->presenter->name,
                        0,
                        (
                            strlen($this->presenter->name) - strlen('Presenter')
                        )
                    )
                ),
                'changeProfilePictureForm'
            ),
            'link'
        );
    }

    public static function createFromComponent(AComponent $component) {}
}