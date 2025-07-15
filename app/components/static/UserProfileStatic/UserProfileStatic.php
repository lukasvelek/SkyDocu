<?php

namespace App\Components\Static\UserProfileStatic;

use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Managers\UserAbsenceManager;
use App\Managers\UserSubstituteManager;
use App\UI\AComponent;

class UserProfileStatic extends AComponent {
    private UserAbsenceManager $userAbsenceManager;
    private UserSubstituteManager $userSubstituteManager;

    private ?UserEntity $user;

    public function __construct(
        HttpRequest $request,
        UserAbsenceManager $userAbsenceManager,
        UserSubstituteManager $userSubstituteManager
    ) {
        parent::__construct($request);

        $this->userAbsenceManager = $userAbsenceManager;
        $this->userSubstituteManager = $userSubstituteManager;

        $this->user = null;
    }

    public function setUser(UserEntity $user) {
        $this->user = $user;
    }
    
    public function render() {
        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $template->user_profile = $this->build();

        return $template;
    }

    private function build() {
        if($this->user === null) {
            throw new GeneralException('No user is set.');
        }

        $code = [];
        $addCode = function(string $title, string $value) use (&$code) {
            $tmp = '<span id="row-' . count($code) . '"><p><b>' . $title . ':</b> ' . $value . '</p></span>';

            $code[] = $tmp;
        };

        $addCode('Fullname', $this->user->getFullname());
        $addCode('Email', $this->user->getEmail() ?? '-');

        return implode('', $code);
    }

    public static function createFromComponent(AComponent $component) {}
}