<?php

namespace App\Managers\Container;

use App\Constants\Container\DocumentStatus;
use App\Constants\Container\Processes\InvoiceCustomMetadata;
use App\Constants\Container\StandaloneProcesses;
use App\Constants\Container\SystemGroups;
use App\Core\DB\DatabaseRow;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Managers\UserManager;
use App\Repositories\Container\PropertyItemsRepository;

class StandaloneProcessManager extends AManager {
    public ProcessManager $processManager;
    private UserEntity $currentUser;
    private UserManager $userManager;
    private DocumentManager $documentManager;
    private FileStorageManager $fileStorageManager;
    private GroupManager $groupManager;
    private FolderManager $folderManager;
    private PropertyItemsRepository $propertyItemsRepository;

    public function __construct(
        Logger $logger,
        ?EntityManager $entityManager,
        ProcessManager $processManager,
        UserEntity $currentUser,
        UserManager $userManager,
        DocumentManager $documentManager,
        FileStorageManager $fileStorageManager,
        GroupManager $groupManager,
        FolderManager $folderManager
    ) {
        parent::__construct($logger, $entityManager);

        $this->processManager = $processManager;
        $this->currentUser = $currentUser;
        $this->userManager = $userManager;
        $this->documentManager = $documentManager;
        $this->fileStorageManager = $fileStorageManager;
        $this->groupManager = $groupManager;
        $this->folderManager = $folderManager;

        $this->propertyItemsRepository = new PropertyItemsRepository($this->processManager->processRepository->conn, $this->logger, $this->processManager->processRepository->transactionLogRepository);
    }

    /**
     *                  PROCESSES
     */

    public function startHomeOffice(array $data) {
        $admin = $this->userManager->getUserByUsername('admin');

        $currentOfficerId = $admin->getId();
        $workflow = [
            $admin->getId()
        ];

        $processId = $this->processManager->startProcess(null, StandaloneProcesses::HOME_OFFICE, $this->currentUser->getId(), $currentOfficerId, $workflow);

        $this->saveProcessData($processId, $data);
    }

    public function startFunctionRequest(array $data) {
        $admin = $this->userManager->getUserByUsername('admin');

        $currentOfficerId = $admin->getId();
        $workflow = [
            $admin->getId()
        ];

        $processId = $this->processManager->startProcess(null, StandaloneProcesses::FUNCTION_REQUEST, $this->currentUser->getId(), $currentOfficerId, $workflow);

        $this->saveProcessData($processId, $data);
    }

    public function startContainerRequest(array $data) {
        $containerManagerId = $this->groupManager->getFirstGroupMemberForGroupTitle(SystemGroups::CONTAINER_MANAGERS);
        if($containerManagerId === null) {
            $containerManagerId = $this->userManager->getUserByUsername('admin')->getId();
        }

        $currentOfficerId = $containerManagerId;
        $workflow = [
            $containerManagerId
        ];

        $processId = $this->processManager->startProcess(null, StandaloneProcesses::CONTAINER_REQUEST, $this->currentUser->getId(), $currentOfficerId, $workflow);

        $this->saveProcessData($processId, $data);
    }

    public function startInvoice(array $data) {
        $accountantId = $this->groupManager->getFirstGroupMemberForGroupTitle(SystemGroups::ACCOUNTANTS)?->userId;
        if($accountantId === null) {
            $accountantId = $this->userManager->getUserByUsername('admin')->getId();
        }

        $currentOfficerId = $accountantId;
        $workflow = [
            $accountantId
        ];

        $processId = $this->processManager->startProcess(null, StandaloneProcesses::INVOICE, $this->currentUser->getId(), $currentOfficerId, $workflow);

        $this->saveProcessData($processId, $data);
    }

    public function finishInvoice(string $processId) {
        // create document
        // create document-file relation
        // create document-process relation

        $processData = $this->getProcessData($processId);
        $process = $this->processManager->getProcessById($processId);

        $class = $this->documentManager->documentClassRepository->getDocumentClassByTitle('Invoices');

        if($class === null) {
            throw new GeneralException('Document class does not exist.');
        }

        $class = DatabaseRow::createFromDbRow($class);

        $folder = $this->folderManager->getFolderByTitle('Invoices');

        $documentMetadata = [
            'title' => 'Invoice ' . $processData['invoiceNo'],
            'authorUserId' => $process->authorUserId,
            'description' => 'Document for Invoice ' . $processData['invoiceNo'], // todo: implement description
            'status' => DocumentStatus::NEW,
            'classId' => $class->classId,
            'folderId' => $folder->folderId
        ];

        $documentCustomMetadata = [
            InvoiceCustomMetadata::COMPANY => $processData['company'],
            InvoiceCustomMetadata::INVOICE_NO => $processData['invoiceNo'],
            InvoiceCustomMetadata::SUM => $processData['sum'],
            InvoiceCustomMetadata::SUM_CURRENCY => $processData['sumCurrency']
        ];

        $folderMetadata = $this->documentManager->getCustomMetadataForFolder($folder->folderId);

        $tmp = [];
        foreach($documentCustomMetadata as $metadataTitle => $value) {
            foreach($folderMetadata as $metadataId => $metadataRow) {
                if($metadataRow->title == $metadataTitle) {
                    $tmp[$metadataId] = $value;
                }
            }
        }

        $documentCustomMetadata = $tmp;

        $documentId = $this->documentManager->createNewDocument($documentMetadata, $documentCustomMetadata);

        $this->fileStorageManager->createNewFileDocumentRelation($documentId, $processData['fileId']);

        if(!$this->processManager->processRepository->updateProcess($processId, [
            'documentId' => $documentId
        ])) {
            throw new GeneralException('Database error.');
        }
    }

    public function startRequestPropertyMove(array $data) {
        $propertyManager = $this->groupManager->getFirstGroupMemberForGroupTitle(SystemGroups::PROPERTY_MANAGERS);

        if($propertyManager === null) {
            throw new GeneralException('Role \'Property manager\' is empty. At least one user must be member.');
        }

        $workflow = [
            $data['user'],
            $propertyManager
        ];

        $currentOfficerId = $workflow[0];

        $processId = $this->processManager->startProcess(null, StandaloneProcesses::REQUEST_PROPERTY_MOVE, $this->currentUser->getId(), $currentOfficerId, $workflow);

        $this->saveProcessData($processId, $data);
    }

    public function finishRequestPropertyMove(string $processId) {
        $processData = $this->getProcessData($processId);
        $process = $this->processManager->getProcessById($processId);
        
        $enumValues = $this->getProcessMetadataEnumValues($process->type, 'items');

        // create user-property relation
        $relationId = $this->createId(EntityManager::C_PROPERTY_ITEMS_USER_RELATION);

        $userId = $processData['user'];
        $itemId = null;

        foreach($enumValues as $row) {
            if($processData['item'] == $row->title2) {
                $itemId = $row->valueId;
            }
        }
        
        if($itemId === null) {
            throw new GeneralException('Item does not exist.');
        }

        if(!$this->propertyItemsRepository->updateExistingUserItemRelationsForItemId($itemId, ['isActive' => 0])) {
            throw new GeneralException('Database error.');
        }

        if(!$this->propertyItemsRepository->createNewUserItemRelation($relationId, $userId, $itemId)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     *                  END OF PROCESSSES
     */

    private function saveProcessData(string $processId, array $data) {
        $entryId = $this->createId(EntityManager::C_PROCESS_DATA);

        if(array_key_exists('btn_submit', $data)) {
            unset($data['btn_submit']);
        }

        if(!$this->processManager->processRepository->insertNewProcessData($entryId, $processId, serialize($data))) {
            throw new GeneralException('Database error.');
        }
    }

    public function getProcessData(string $processId) {
        return $this->processManager->processRepository->getProcessDataForProcess($processId);
    }

    public function updateProcessType(string $typeKey, array $data) {
        if(!$this->processManager->processRepository->updateProcessType($typeKey, $data)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getEnabledProcessTypes() {
        $qb = $this->processManager->processRepository->composeQueryForProcessTypes();
        $qb->andWhere('isEnabled = 1')
            ->execute();

        $rows = [];
        while($row = $qb->fetchAssoc()) {
            $rows[] = DatabaseRow::createFromDbRow($row);
        }

        return $rows;
    }

    public function composeQueryForProcessTypeInstances(string $processType) {
        $qb = $this->processManager->processRepository->commonComposeQuery(false);
        $qb->andWhere('type = ?', [$processType]);
        return $qb;
    }

    public function composeQueryForProcessMetadataForProcess(string $typeId) {
        $qb = $this->processManager->processRepository->composeQueryForProcessMetadata();
        $qb->where('typeId = ?', [$typeId]);
        return $qb;
    }

    public function getProcessMetadataForProcess(string $typeId) {
        $qb = $this->processManager->processRepository->composeQueryForProcessMetadata();
        $qb->where('typeId = ?', [$typeId])
            ->execute();

        $metadata = [];
        while($row = $qb->fetchAssoc()) {
            $row = DatabaseRow::createFromDbRow($row);

            $metadata[] = $row;
        }

        return $metadata;
    }

    public function composeQueryForProcessMetadataEnumForMetadata(string $metadataId) {
        $qb = $this->processManager->processRepository->composeQueryForProcessMetadataListValues();
        $qb->where('metadataId = ?', [$metadataId]);
        return $qb;
    }

    public function getProcessMetadataEnumValues(string $processTitle, string $metadataTitle, ?string $searchQuery = null) {
        $qb = $this->processManager->processRepository->composeQueryForProcessTypes();
        $qb->where('typeKey = ?', [$processTitle])
            ->execute();

        $result = $qb->fetch();

        if($result === null) {
            return [];
        }

        $type = DatabaseRow::createFromDbRow($result);

        $qb = $this->composeQueryForProcessMetadataForProcess($type->typeId);
        $qb->andWhere('title = ?', [$metadataTitle])
            ->execute();

        $result = $qb->fetch();

        if($result === null) {
            return [];
        }

        $metadata = DatabaseRow::createFromDbRow($result);

        $qb = $this->composeQueryForProcessMetadataEnumForMetadata($metadata->metadataId);

        if($searchQuery !== null) {
            $qb->andWhere('title LIKE :title')
                ->setParams([':title' => '%' . $searchQuery . '%']);
        }

        $qb->orderBy('title')
            ->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $row = DatabaseRow::createFromDbRow($row);

            $values[] = $row;
        }

        return $values;
    }

    public function createMetadataEnumValue(string $metadataId, string $title, ?string $title2, ?string $title3) {
        $valueId = $this->createId(EntityManager::C_PROCESS_CUSTOM_METADATA_LIST_VALUES);

        $data = [
            'valueId' => $valueId,
            'metadataId' => $metadataId,
            'title' => $title
        ];

        if($title2 !== null) {
            $data['title2'] = $title2;
        }
        if($title3 !== null) {
            $data['title3'] = $title3;
        }

        $lastKey = $this->processManager->processRepository->getLastMetadataEnumValueKey($metadataId);
        if($lastKey !== null) {
            $data['metadataKey'] = ((int)$lastKey) + 1;
        } else {
            $data['metadataKey'] = 1;
        }

        if(!$this->processManager->processRepository->createNewMetadataEnumValue($data)) {
            throw new GeneralException('Database error.');
        }

        return $valueId;
    }

    public function deleteMetadataEnumValue(string $valueId) {
        if(!$this->processManager->processRepository->deleteProcessMetadataEnumValue($valueId)) {
            throw new GeneralException('Database error.');
        }
    }

    public function getMetadataEnumValueById(string $valueId) {
        $row = $this->processManager->processRepository->getMetadataEnumValueById($valueId);

        if($row === null) {
            throw new NonExistingEntityException('Metadata enum value does not exist.');
        }

        return DatabaseRow::createFromDbRow($row);
    }

    public function updateMetadataEnumValue(string $valueId, string $title) {
        if(!$this->processManager->processRepository->updateMetadataEnumValue($valueId, $title)) {
            throw new GeneralException('Database error.');
        }
    }

    public function composeQueryForProcessData() {
        return $this->processManager->processRepository->composeQueryForProcessData();
    }

    public function getProcessMetadataByTitleAndProcessTitle(string $metadataTitle, string $processTitle) {
        $processTypes = $this->getEnabledProcessTypes();

        $processTypeId = null;
        foreach($processTypes as $processType) {
            if($processType->typeKey == $processTitle) {
                $processTypeId = $processType->typeId;
            }
        }

        if($processTypeId === null) {
            throw new NonExistingEntityException('Process type does not exist.');
        }

        $qb = $this->processManager->processRepository->composeQueryForProcessMetadata();
        $qb->andWhere('title = ?', [$metadataTitle])
            ->andWhere('typeId = ?', [$processTypeId])
            ->execute();

        $row = $qb->fetch();

        if($row === null) {
            throw new NonExistingEntityException('Process metadata does not exist.');
        }

        return DatabaseRow::createFromDbRow($row);
    }
}

?>