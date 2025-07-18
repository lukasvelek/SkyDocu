<?php

namespace App\Core;

use App\Exceptions\GeneralException;

/**
 * FileUploadManager is used for uploading files
 * 
 * @author Lukas Velek
 */
class FileUploadManager {
    public const FILE_FILENAME = 'filename';
    public const FILE_FILESIZE = 'filesize';
    public const FILE_FILEPATH = 'filepath';

    public const FILE_TYPE_IMAGE = 1;
    public const FILE_TYPE_DOCUMENT = 2;

    /**
     * Allowed document file extensions
     */
    public static array $ALLOWED_EXTENSIONS = [
        'docx',
        'pdf',
        'txt',
        'doc',
        'ppt',
        'pptx'
    ];

    /**
     * Allowed image file extensions
     */
    public static array $ALLOWED_IMAGE_EXTENSIONS = [
        'png',
        'jpg',
        'jpeg'
    ];

    /**
     * Uploads a file for process instance
     * 
     * @param array $fileData Result of $_FILES[FILE_INPUT_NAME]
     * @param string $userId User ID
     * @param ?string $containerId Container ID
     * @param array $additionalValues Additional values
     */
    public function uploadFileForProcessInstance(array $fileData, string $userId, ?string $containerId, array $additionalValues = []): array {
        $dirpath = $this->generateFolderPath($userId, $containerId);
        $filepath = $dirpath . $this->generateFilename($fileData['name'], $userId, $containerId, $additionalValues);

        // CHECKS
        if(!$this->checkType($filepath, self::FILE_TYPE_DOCUMENT)) {
            throw new GeneralException('File extension for file \'' . $fileData['name'] . '\' is not supported. Supported file extensions are: ' . implode(', ', array_merge(self::$ALLOWED_EXTENSIONS, self::$ALLOWED_IMAGE_EXTENSIONS)));
        }
        if(!$this->checkFileSize($fileData)) {
            throw new GeneralException('File is too big. Only files with size up to 500 MB are supported.');
        }
        // END OF CHECKS

        if(!$this->createFolderPath($dirpath)) {
            throw new GeneralException('Could not create end file path.');
        }

        if(move_uploaded_file($fileData['tmp_name'], $filepath)) {
            return [
                self::FILE_FILENAME => $fileData['name'],
                self::FILE_FILEPATH => $filepath,
                self::FILE_FILESIZE => $this->getFileSize($fileData)
            ];
        } else {
            throw new GeneralException('File upload error.');
        }
    }

    /**
     * Uploads a file
     * 
     * @param array $fileData Result of $_FILES[FILE_INPUT_NAME]
     * @param string $userId User ID
     * @param ?string $containerId Container ID
     * @param array $additionalValues Additional values
     */
    public function uploadFile(array $fileData, string $userId, ?string $containerId, array $additionalValues = []): array {
        $dirpath = $this->generateFolderPath($userId, $containerId);
        $filepath = $dirpath . $this->generateFilename($fileData['name'], $userId, $containerId, $additionalValues);

        // CHECKS
        if(!$this->checkType($filepath, self::FILE_TYPE_DOCUMENT)) {
            throw new GeneralException('File extension for file \'' . $fileData['name'] . '\' is not supported. Supported file extensions are: ' . implode(', ', array_merge(self::$ALLOWED_EXTENSIONS, self::$ALLOWED_IMAGE_EXTENSIONS)));
        }
        if(!$this->checkFileSize($fileData)) {
            throw new GeneralException('File is too big. Only files with size up to 500 MB are supported.');
        }
        // END OF CHECKS

        if(!$this->createFolderPath($dirpath)) {
            throw new GeneralException('Could not create end file path.');
        }

        if(move_uploaded_file($fileData['tmp_name'], $filepath)) {
            return [
                self::FILE_FILENAME => $fileData['name'],
                self::FILE_FILEPATH => $filepath,
                self::FILE_FILESIZE => $this->getFileSize($fileData)
            ];
        } else {
            throw new GeneralException('File upload error.');
        }
    }

    public function uploadImage(array $fileData, string $userId, ?string $containerId, array $additionalValues = []): array {
        $dirpath = $this->generateFolderPath($userId, $containerId);
        $filepath = $dirpath . $this->generateFilename($fileData['name'], $userId, $containerId, $additionalValues);

        // CHECKS
        if(!$this->checkType($filepath, self::FILE_TYPE_IMAGE)) {
            throw new GeneralException('File extension for file \'' . $fileData['name'] . '\' is not supported. Supported file extensions are: ' . implode(', ', self::$ALLOWED_IMAGE_EXTENSIONS));
        }
        if(!$this->checkFileSize($fileData)) {
            throw new GeneralException('File is too big. Only files with size up to 500 MB are supported.');
        }
        // END OF CHECKS

        if(!$this->createFolderPath($dirpath)) {
            throw new GeneralException('Could not create end file path.');
        }

        if(move_uploaded_file($fileData['tmp_name'], $filepath)) {
            return [
                self::FILE_FILENAME => $fileData['name'],
                self::FILE_FILEPATH => $filepath,
                self::FILE_FILESIZE => $this->getFileSize($fileData)
            ];
        } else {
            throw new GeneralException('File upload error.');
        }
    }

    /**
     * Creates a file
     * 
     * @param string $name File name
     * @param string $content File content
     * @param string $userId User ID
     * @param ?string $containerId Container ID
     * @param array $additionalValues Additional values
     */
    public function createFile(string $name, string $content, string $userId, ?string $containerId, array $additionalValues = []): array {
        $dirpath = $this->generateFolderPath($userId, $containerId);
        $filename = $this->generateFilename($name, $userId, $containerId, $additionalValues);
        $filepath = $dirpath . $filename;

        // CHECKS
        if(!$this->checkType($filepath, self::FILE_TYPE_DOCUMENT)) {
            throw new GeneralException('File extension for file \'' . $filepath . '\' is not supported. Supported file extensions are: ' . implode(', ', self::$ALLOWED_EXTENSIONS));
        }
        // END OF CHECKS

        if(!$this->createFolderPath($dirpath)) {
            throw new GeneralException('Could not create end file path.');
        }

        $filesize = FileManager::saveFile($dirpath, $filename, $content, false, false);

        if($filesize !== false) {
            return [
                self::FILE_FILENAME => $name,
                self::FILE_FILEPATH => $filepath,
                self::FILE_FILESIZE => $filesize
            ];
        } else {
            throw new GeneralException('File creation error.');
        }
    }

    /**
     * Checks file extension
     * 
     * @param string $filepath Filepath
     * @param int $fileType File type
     */
    private function checkType(string $filepath, int $fileType): bool {
        $type = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if($fileType == self::FILE_TYPE_IMAGE) {
            if(in_array($type, self::$ALLOWED_IMAGE_EXTENSIONS)) {
                return true;
            }
        } else {
            if(in_array($type, self::$ALLOWED_EXTENSIONS)) {
                return true;
            }

            if(in_array($type, self::$ALLOWED_IMAGE_EXTENSIONS)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns file size
     * 
     * @param array $fileData
     */
    private function getFileSize(array $fileData): int {
        return $fileData['size'];
    }

    /**
     * Checks file size
     * 
     * @param array $fileData
     */
    private function checkFileSize(array $fileData) {
        $size = $this->getFileSize($fileData);

        return $size <= 500_000_000;
    }

    /**
     * Generates end directory path
     * 
     * @param string $userId User ID
     * @param ?string $containerId Container ID
     */
    private function generateFolderPath(string $userId, ?string $containerId): string {
        $path = APP_ABSOLUTE_DIR . UPLOAD_DIR . $userId . '\\' . ($containerId !== null ? ($containerId . '\\') : '');

        return $path;
    }

    /**
     * Creates folder path
     * 
     * @param string $path Folder path
     */
    private function createFolderPath(string $path): bool {
        return FileManager::createFolder($path, true);
    }

    /**
     * Generates filename
     * 
     * @param string $filename Filename
     * @param string $userId User ID
     * @param string $containerId Container ID
     * @param array $additionalValues Additional values for file name generation
     */
    private function generateFilename(string $filename, string $userId, ?string $containerId, array $additionalValues = []) {
        $hash = HashManager::createHash(8, false);
        $text = $hash . $filename . $userId . time() . rand(0, 100);
        if($containerId !== null) {
            $text .= $containerId;
        }
        $text .= implode('', $additionalValues);
        return md5($text) . '.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

?>