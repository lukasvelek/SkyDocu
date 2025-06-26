<?php

namespace App\Core;

use App\Exceptions\FileDoesNotExistException;

/**
 * FileManager allows manipulating with files.
 * 
 * @author Lukas Velek
 */
class FileManager {
    /**
     * Checks if file exists
     * 
     * @param string $filePath Path to the file
     * @return bool True if file exists or false if not
     */
    public static function fileExists(string $filePath) {
        return file_exists($filePath);
    }

    /**
     * Loads file content
     * 
     * @param string $filePath Path to the file
     * @return string|false File content or false if error occurred
     * @throws FileDoesNotExistException
     */
    public static function loadFile(string $filePath) {
        if(!self::fileExists($filePath)) {
            throw new FileDoesNotExistException($filePath);
        }

        $content = file_get_contents($filePath);

        return $content;
    }

    /**
     * Loads file content line by line and returns an array of file lines
     * 
     * @param string $filePath Path to the file
     * @throws FileDoesNotExistException
     */
    public static function loadFileLineByLine(string $filePath): array {
        $lines = [];

        if(!self::fileExists($filePath)) {
            throw new FileDoesNotExistException($filePath);
        }

        $handle = fopen($filePath, 'r');
        if($handle) {
            while(($line = fgets($handle)) !== false) {
                $lines[] = $line;
            }

            fclose($handle);
        }

        return $lines;
    }

    /**
     * Checks if folder exists
     * 
     * @param string $dirPath Path to the directory
     * @return bool True if directory exists or false if not
     */
    public static function folderExists(string $dirPath) {
        return is_dir($dirPath);
    }

    /**
     * Returns all files in a given directory
     * 
     * @param string $dirPath Path to the directory (root in method's context)
     * @param bool $recursiveEnabled Is recursive file finding enabled?
     * @return array Array of filenames (key is relative path and value is absolute path)
     */
    public static function getFilesInFolder(string $dirPath, bool $recursiveEnabled = true) {
        $recursive = function (string $dirPath, array &$objects) use ($recursiveEnabled, &$recursive) {
            $contents = scandir($dirPath);

            unset($contents[0], $contents[1]);

            foreach($contents as $content) {
                $realObject = $dirPath . '\\' . $content;

                if(!is_dir($realObject)) {
                    $objects[$content] = $realObject;
                } else {
                    if($recursiveEnabled === true) {
                        $recursive($realObject, $objects);
                    }
                }
            }
        };

        $objects = [];

        $recursive($dirPath, $objects);

        return $objects;
    }

    /**
     * Writes (or if $overwrite is true, then appends) content to given file. If file exists and the content is supposed to be appended, 
     * then a file lock is created and after successful the file is sucessfully saved, the file is unlocked.
     * 
     * @param string $path Path to the directory where the file will be saved
     * @param string $filename Filename
     * @param string|array $fileContent File content
     * @param bool $overwrite True if file content should be overwritten or false if not
     * @param bool $appendNewLine Only applicable if $fileContent is array. True if each element in $fileContent should be on a new line, or false if not
     * @return int|false Number of bytes written or false if error occurred
     */
    public static function saveFile(string $path, string $filename, string|array $fileContent, bool $overwrite = false, bool $appendNewLine = true) {
        if(is_array($fileContent)) {
            if($appendNewLine) {
                $fileContent = implode("\r\n", $fileContent);
            } else {
                $fileContent = implode('', $fileContent);
            }
        }

        if(!self::folderExists($path)) {
            self::createFolder($path, true);
        }

        if($overwrite === false) {
            return file_put_contents($path . $filename, $fileContent, FILE_APPEND);
        } else {
            return file_put_contents($path . $filename, $fileContent);
        }
    }

    /**
     * Creates a directory
     * 
     * @param string $dirPath Path to the directory
     * @param bool $recursive True if directory should be created recursively or false if not
     * @return bool True on success or false on failure
     */
    public static function createFolder(string $dirPath, bool $recursive = false) {
        if(is_dir($dirPath)) {
            return true;
        }
        if(!FileManager::folderExists($dirPath)) {
            return mkdir($dirPath, 0777, $recursive);
        }
    }

    /**
     * Deletes a folder recursively
     * 
     * @param string $dirPath Path to the directory that should be deleted
     * @return bool True on success or false on failure
     */
    public static function deleteFolderRecursively(string $dirPath, bool $root = true) {
        if(is_dir($dirPath)) {
            $result = true;
            $objects = scandir($dirPath);
            
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dirPath . DIRECTORY_SEPARATOR . $object) && !is_link($dirPath . DIRECTORY_SEPARATOR . $object)) {
                        $r = self::deleteFolderRecursively($dirPath . $object . DIRECTORY_SEPARATOR, false);
                        if($r !== true && $result !== false) {
                            $result = $r;
                        }
                    } else {
                        file_put_contents(APP_ABSOLUTE_DIR . LOG_DIR . '__filesDeleted.log', $dirPath . $object . "\r\n", FILE_APPEND);
                        $r = self::deleteFile($dirPath . $object);
                        if($result !== false) {
                            $result = $r;
                        }
                    }
                }
            }

            if(!$root) {
                if(self::folderExists($dirPath)) {
                    return rmdir($dirPath) && $result;
                } else {
                    return $result;
                }
            } else {
                return $result;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Deletes a file
     * 
     * @param string $filePath Path to the file
     * @return bool True on success or false on failure
     */
    public static function deleteFile(string $filePath) {
        $result = unlink($filePath);
        return $result;
    }

    /**
     * Moves a file from one destination to another
     * 
     * @param string $oldPath Old path
     * @param string $newPath New path
     * @return bool True on success or false on failure
     */
    public static function moveFile(string $oldPath, string $newPath) {
        return rename($oldPath, $newPath);
    }

    /**
     * Returns filename from given path
     * 
     * @param string $path Path to the file
     * @param bool $returnExtension True if file extension should be returned as well
     * @return string Filename
     */
    public static function getFilenameFromPath(string $path, bool $returnExtension = false): string {
        $parts = explode('\\', $path);

        $filenameWithExtension = $parts[count($parts) - 1];

        if($returnExtension) {
            return $filenameWithExtension;
        } else {
            return explode('.', $filenameWithExtension)[0];
        }
    }
}

?>