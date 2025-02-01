<?php

namespace App\Authorizators;

use App\Constants\Container\DocumentStatus;
use App\Constants\Container\SystemGroups;
use App\Core\DatabaseConnection;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\Container\ArchiveManager;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessManager;
use App\Managers\UserManager;
use App\Repositories\Container\DocumentRepository;
use Exception;

/**
 * DocumentBulkActionAuthorizator is used for checking if user is able to perform bulk action
 * 
 * @author Lukas Velek
 */
class DocumentBulkActionAuthorizator extends AAuthorizator {
    private DocumentManager $documentManager;
    private DocumentRepository $documentRepository;
    private UserManager $userManager;
    private GroupManager $containerGroupManager;
    private ProcessManager $processManager;
    private ArchiveManager $archiveManager;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db
     * @param Logger $logger
     * @param DocumentManager $documentManager
     * @param DocumentRepository $documentRepository
     * @param UserManager $userManager
     * @param GroupManager $containerGroupManager
     * @param ProcessManager $processManager
     * @param ArchiveManager $archiveManager
     */
    public function __construct(
        DatabaseConnection $db,
        Logger $logger,
        DocumentManager $documentManager,
        DocumentRepository $documentRepository,
        UserManager $userManager,
        GroupManager $containerGroupManager,
        ProcessManager $processManager,
        ArchiveManager $archiveManager
    ) {
        parent::__construct($db, $logger);

        $this->documentManager = $documentManager;
        $this->documentRepository = $documentRepository;
        $this->userManager = $userManager;
        $this->containerGroupManager = $containerGroupManager;
        $this->processManager = $processManager;
        $this->archiveManager = $archiveManager;
    }

    /**
     * Checks if user can execute archivation
     * 
     * @param string $userId
     * @param string $documentId
     * @return bool True if user can execute or false if not
     */
    public function canExecuteArchivation(string $userId, string $documentId) {
        return $this->internalExecute('throwExceptionIfCannotExecuteArchivation', $userId, $documentId);
    }

    /**
     * Throws exception if user cannot execute archivation
     * 
     * @param string $userId
     * @param string $documentId
     */
    public function throwExceptionIfCannotExecuteArchivation(string $userId, string $documentId) {
        if(!in_array($userId, $this->containerGroupManager->getUsersForGroupTitle(SystemGroups::ARCHIVISTS))) {
            throw new GeneralException('User is not member of the Archivists group.', null, false);
        }

        $document = $this->documentManager->getDocumentById($documentId);

        if($document->status != DocumentStatus::NEW) {
            throw new GeneralException(sprintf('Document\'s status must be \'%s\' but it is \'%s\'.', DocumentStatus::toString(DocumentStatus::NEW), DocumentStatus::toString($document->status)), null, false);
        }

        if($this->archiveManager->isDocumentInArchiveFolder($documentId)) {
            throw new GeneralException('Document must not be in an archive folder.', null, false);
        }

        $this->checkDocumentIsInProcess($documentId);
    }

    /**
     * Checks if user can execute shredding request
     * 
     * @param string $userId
     * @param string $documentId
     * @return bool True if user can execute or false if not
     */
    public function canExecuteShreddingRequest(string $userId, string $documentId) {
        return $this->internalExecute('throwExceptionIfCannotExecuteShreddingRequest', $userId, $documentId);
    }

    /**
     * Throws exception if user cannot execute shredding request
     * 
     * @param string $userId
     * @param string $documentId
     */
    public function throwExceptionIfCannotExecuteShreddingRequest(string $userId, string $documentId) {
        $document = $this->documentManager->getDocumentById($documentId);

        if($document->status != DocumentStatus::ARCHIVED) {
            throw new GeneralException(sprintf('Document\'s status must be \'%s\' or \'%s\' but it is \'%s\'.', DocumentStatus::toString(DocumentStatus::NEW), DocumentStatus::toString(DocumentStatus::ARCHIVED), DocumentStatus::toString($document->status)), null, false);
        }

        $this->checkDocumentIsInProcess($documentId);
    }

    /**
     * Checks if user can execute shredding
     * 
     * @param string $userId
     * @param string $documentId
     * @return bool True if user can execute or false if not
     */
    public function canExecuteShredding(string $userId, string $documentId) {
        return $this->internalExecute('throwExceptionIfCannotExecuteShredding', $userId, $documentId);
    }

    /**
     * Throws exception if user cannot execute shredding
     * 
     * @param string $userId
     * @param string $documentId
     */
    public function throwExceptionIfCannotExecuteShredding(string $userId, string $documentId) {
        $document = $this->documentManager->getDocumentById($documentId);

        if($document->status != DocumentStatus::READY_FOR_SHREDDING) {
            throw new GeneralException(sprintf('Document\'s status must be \'%s\' but it is \'%s\'.', DocumentStatus::toString(DocumentStatus::READY_FOR_SHREDDING), DocumentStatus::toString($document->status)), null, false);
        }

        $this->checkDocumentIsInProcess($documentId);
    }

    /**
     * Calls the throwExceptionIfCannotExecuteX() method and returns true if it doesn't throw any exception and false if it throws an exception
     * 
     * @param string $name Method name
     * @param mixed $params Parameters
     * @return bool True if no exception was thrown or false if an exception was thrown
     */
    private function internalExecute(string $name, mixed ...$params) {
        $result = true;
        try {
            $this->{$name}(...$params);
        } catch(AException|Exception $e) {
            $result = false;
        }
        return $result;
    }

    /**
     * Checks if a document is in a process and throws an exception if it is
     * 
     * @param string $documentId
     */
    private function checkDocumentIsInProcess(string $documentId) {
        if($this->processManager->isDocumentInProcess($documentId)) {
            throw new GeneralException('Document is already in a process.', null, false);
        }
    }
}

?>