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

    public static array $ALLOWED_EXTENSIONS = [
        'docx',
        'pdf',
        'txt',
        'doc',
        'ppt',
        'pptx'
    ];

    /**
     * Uploads a file
     * 
     * @param array $fileData Result of $_FILES[FILE_INPUT_NAME]
     * @param string $documentId Document ID
     * @param string $userId User ID
     */
    public function uploadFile(array $fileData, ?string $documentId, string $userId): array {
        $dirpath = $this->generateFolderPath($documentId, $userId);
        $filepath = $dirpath . $this->generateFilename($fileData['name'], $documentId, $userId);

        // CHECKS
        if(!$this->checkType($filepath)) {
            throw new GeneralException('File extension for file \'' . $filepath . '\' is not supported. Supported file extensions are: ' . implode(', ', self::$ALLOWED_EXTENSIONS));
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
     * @param ?string $documentId Document ID
     * @param string $userId User ID
     */
    public function createFile(string $name, string $content, ?string $documentId, string $userId): array {
        $dirpath = $this->generateFolderPath($documentId, $userId);
        $filename = $this->generateFilename($name, $documentId, $userId);
        $filepath = $dirpath . $filename;

        // CHECKS
        if(!$this->checkType($filepath)) {
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
     */
    private function checkType(string $filepath): bool {
        $type = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        return in_array($type, self::$ALLOWED_EXTENSIONS);
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
     * @param string $documentId Document ID
     * @param string $userId User ID
     */
    private function generateFolderPath(?string $documentId, string $userId): string {
        $path = APP_ABSOLUTE_DIR . CONTAINERS_DIR . 'uploads\\' . $userId . '\\';

        if($documentId !== null) {
            $path .= $documentId . '\\';
        }

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
     * @param string $documentId Document ID
     * @param string $userId User ID
     */
    private function generateFilename(string $filename, ?string $documentId, string $userId) {
        $hash = HashManager::createHash(8, false);
        $text = $hash . $filename . $userId;
        if($documentId !== null) {
            $text .= $documentId;
        }
        return md5($text) . '.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

?>