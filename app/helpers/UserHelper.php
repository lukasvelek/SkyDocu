<?php

namespace App\Helpers;

use App\Core\FileManager;
use App\Entities\UserEntity;
use App\Managers\FileStorageManager;

/**
 * UserHelper contains useful methods for working with users
 * 
 * @author Lukas Velek
 */
class UserHelper {
    /**
     * Returns user profile picture URI
     */
    public static function getUserProfilePictureUri(
        UserEntity $user,
        FileStorageManager $fileStorageManager
    ) {
        $defaultImageSource = 'resources/images/user-profile-picture.png';

        if($user->getProfilePictureFileId() === null) {
            return $defaultImageSource;
        }

        $fileId = $user->getProfilePictureFileId();

        $file = $fileStorageManager->getFileById($fileId);

        return FileManager::getRelativeFilePathFromAbsoluteFilePath($file->filepath);
    }
}