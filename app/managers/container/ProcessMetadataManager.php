<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessMetadataRepository;

class ProcessMetadataManager extends AManager {
    public ProcessMetadataRepository $processMetadataRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ProcessMetadataRepository $processMetadataRepository) {
        parent::__construct($logger, $entityManager);

        $this->processMetadataRepository = $processMetadataRepository;
    }

    public function getProcessMetadataById(string $metadataId): DatabaseRow {
        $metadata = $this->processMetadataRepository->getProcessMetadataById($metadataId);

        if($metadata === null) {
            throw new GeneralException('No process metadata \'' . $metadataId . '\' exists.');
        }

        return DatabaseRow::createFromDbRow($metadata);
    }

    public function addNewMetadataValue(array $data) {
        // add value id
        $valueId = $this->createId(EntityManager::C_PROCESS_CUSTOM_METADATA_VALUES);

        $data['valueId'] = $valueId;

        if(!$this->processMetadataRepository->insertNewMetadataValue($data)) {
            throw new GeneralException('Database error.');
        }
    }
}

?>