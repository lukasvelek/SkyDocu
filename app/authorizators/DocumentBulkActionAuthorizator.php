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
use App\Managers\UserManager;
use App\Repositories\Container\DocumentRepository;
use Exception;

class DocumentBulkActionAuthorizator extends AAuthorizator {
    private DocumentManager $dm;
    private DocumentRepository $dr;
    private UserManager $um;
    private GroupManager $cgm;

    public function __construct(DatabaseConnection $db, Logger $logger, DocumentManager $dm, DocumentRepository $dr, UserManager $um, GroupManager $cgm) {
        parent::__construct($db, $logger);

        $this->dm = $dm;
        $this->dr = $dr;
        $this->um = $um;
        $this->cgm = $cgm;
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

    public function canExecuteShredding(string $userId, string $documentId) {
        return $this->internalExecute('throwExceptionIfCannotExecuteShredding', $userId, $documentId);
    }

    public function throwExceptionIfCannotExecuteShredding(string $userId, string $documentId) {

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
}

?>