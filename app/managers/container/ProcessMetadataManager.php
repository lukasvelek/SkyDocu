<?php

namespace App\Managers\Container;

use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\ProcessMetadataRepository;

/**
 * ProcessMetadataManager contains high-level database operations for process metadata
 * 
 * @author Lukas Velek
 */
class ProcessMetadataManager extends AManager {
    public ProcessMetadataRepository $processMetadataRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, ProcessMetadataRepository $processMetadataRepository) {
        parent::__construct($logger, $entityManager);

        $this->processMetadataRepository = $processMetadataRepository;
    }

    /**
     * Returns a DatabaseRow instance for process metadata by metadata ID
     * 
     * @param string $metadataId
     * @throws GeneralException
     */
    public function getProcessMetadataById(string $metadataId): DatabaseRow {
        $metadata = $this->processMetadataRepository->getProcessMetadataById($metadataId);

        if($metadata === null) {
            throw new GeneralException('No process metadata \'' . $metadataId . '\' exists.');
        }

        return DatabaseRow::createFromDbRow($metadata);
    }

    /**
     * Adds a new metadata value
     * 
     * @param array $data Data
     * @throws GeneralException
     */
    public function addNewMetadataValue(array $data) {
        // add value id
        $valueId = $this->createId(EntityManager::C_PROCESS_CUSTOM_METADATA_VALUES);

        $data['valueId'] = $valueId;

        if(!$this->processMetadataRepository->insertNewMetadataValue($data)) {
            throw new GeneralException('Database error.');
        }
    }

    /**
     * Returns a DatabaseRow instance for process metadata by unique process ID and metadata title
     * 
     * @param string $uniqueProcessId Unique process ID
     * @param string $title Metadata title
     * @throws GeneralException
     */
    public function getProcessMetadataByTitle(string $uniqueProcessId, string $title): DatabaseRow {
        $metadata = $this->processMetadataRepository->getProcessMetadataByTitleAndUniqueProcessId($uniqueProcessId, $title);

        if($metadata === null) {
            throw new GeneralException('No process metadata for unique process ID \'' . $uniqueProcessId . '\' with title \'' . $title . '\' exists.');
        }

        return DatabaseRow::createFromDbRow($metadata);
    }

    /**
     * Searches metadata values for unique process ID and metadata title
     * 
     * @param string $uniqueProcessId Unique process ID
     * @param string $metadataTitle Metadata title
     * @param string $query Query
     */
    public function searchMetadataValuesForUniqueProcessId(string $uniqueProcessId, string $metadataTitle, string $query): array {
        $metadata = $this->getProcessMetadataByTitle($uniqueProcessId, $metadataTitle);

        $qb = $this->processMetadataRepository->composeQueryForProcessMetadataValues($metadata->metadataId);
        $qb->andWhere('title LIKE ?', ["%$query%"])
            ->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $values[] = DatabaseRow::createFromDbRow($row);
        }

        return $values;
    }

    /**
     * Returns all metadata for unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function getMetadataForUniqueProcessId(string $uniqueProcessId): array {
        $qb = $this->processMetadataRepository->composeQueryForProcessMetadata($uniqueProcessId);
        $qb->execute();

        $metadata = [];
        while($row = $qb->fetchAssoc()) {
            $metadata[] = DatabaseRow::createFromDbRow($row);
        }

        return $metadata;
    }

    /**
     * Returns all metadata values for unique process ID
     * 
     * @param string $uniqueProcessId Unique process ID
     * @param string $metadataTitle Metadata title
     */
    public function getMetadataValuesForUniqueProcessId(string $uniqueProcessId, string $metadataTitle) {
        $metadata = $this->getProcessMetadataByTitle($uniqueProcessId, $metadataTitle);

        $qb = $this->processMetadataRepository->composeQueryForProcessMetadataValues($metadata->metadataId);
        $qb->execute();

        $values = [];
        while($row = $qb->fetchAssoc()) {
            $values[] = DatabaseRow::createFromDbRow($row);
        }

        return $values;
    }
}

?>