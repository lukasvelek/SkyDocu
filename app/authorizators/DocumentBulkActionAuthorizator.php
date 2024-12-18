<?php

namespace App\Authorizators;

use App\Constants\Container\DocumentStatus;
use App\Constants\Container\SystemGroups;
use App\Core\DatabaseConnection;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\Container\DocumentManager;
use App\Managers\Container\GroupManager;
use App\Managers\Container\ProcessManager;
use App\Managers\UserManager;
use App\Repositories\Container\DocumentRepository;
use Exception;

class DocumentBulkActionAuthorizator extends AAuthorizator {
    private DocumentManager $dm;
    private DocumentRepository $dr;
    private UserManager $um;
    private GroupManager $cgm;
    private ProcessManager $pm;

    public function __construct(DatabaseConnection $db, Logger $logger, DocumentManager $dm, DocumentRepository $dr, UserManager $um, GroupManager $cgm, ProcessManager $pm) {
        parent::__construct($db, $logger);

        $this->dm = $dm;
        $this->dr = $dr;
        $this->um = $um;
        $this->cgm = $cgm;
        $this->pm = $pm;
    }

    public function canExecuteArchivation(string $userId, array $documentIds) {
        return $this->internalExecute('throwExceptionIfCannotExecuteArchivation', $userId, $documentIds);
    }

    public function throwExceptionIfCannotExecuteArchivation(string $userId, array $documentIds) {
        if(!in_array($userId, $this->cgm->getUsersForGroupTitle(SystemGroups::ARCHIVISTS))) {
            throw new GeneralException('User is not member of the Archivists group.', null, false);
        }

        foreach($documentIds as $documentId) {
            $document = $this->dm->getDocumentById($documentId);

            if($document->status != DocumentStatus::NEW) {
                throw new GeneralException('Document\'s status must be \'new\' but it is \'' . DocumentStatus::toString($document->status) . '\'.', null, false);
            }
        }
    }

    public function canExecuteShreddingRequest(string $userId, string $documentId) {
        return $this->internalExecute('throwExceptionIfCannotExecuteShreddingRequest', $userId, $documentId);
    }

    public function throwExceptionIfCannotExecuteShreddingRequest(string $userId, string $documentId) {
        $document = $this->dm->getDocumentById($documentId);

        if(!in_array($document->status, [DocumentStatus::NEW, DocumentStatus::ARCHIVED])) {
            throw new GeneralException(sprintf('Document\'s status must be \'%s\' or \'%s\' but it is \'%s\'.', DocumentStatus::toString(DocumentStatus::NEW), DocumentStatus::toString(DocumentStatus::ARCHIVED), DocumentStatus::toString($document->status)), null, false);
        }

        $this->checkDocumentIsInProcess($documentId);
    }

    public function canExecuteShredding(string $userId, string $documentId) {
        return $this->internalExecute('throwExceptionIfCannotExecuteShredding', $userId, $documentId);
    }

    public function throwExceptionIfCannotExecuteShredding(string $userId, string $documentId) {
        $document = $this->dm->getDocumentById($documentId);

        if($document->status != DocumentStatus::READY_FOR_SHREDDING) {
            throw new GeneralException(sprintf('Document\'s status must be \'%s\' but it is \'%s\'.', DocumentStatus::toString(DocumentStatus::READY_FOR_SHREDDING), DocumentStatus::toString($document->status)));
        }

        $this->checkDocumentIsInProcess($documentId);
    }

    private function internalExecute(string $name, mixed ...$params) {
        $result = true;
        try {
            $this->{$name}(...$params);
        } catch(AException|Exception $e) {
            $result = false;
        }
        return $result;
    }

    private function checkDocumentIsInProcess(string $documentId) {
        if($this->pm->isDocumentInProcess($documentId)) {
            throw new GeneralException('Document already is in a process.', null, false);
        }
    }
}

?>