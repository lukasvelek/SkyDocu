<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;
use QueryBuilder\QueryBuilder;

/**
 * ProcessMetadataRepository contains low-level database operations for process metadata
 * 
 * @author Lukas Velek
 */
class ProcessMetadataRepository extends ARepository {
    /**
     * Composes an instance of QueryBuilder for process metadata with given $uniqueProcessId
     * 
     * @param string $uniqueProcessId Unique process ID
     */
    public function composeQueryForProcessMetadata(string $uniqueProcessId): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata')
            ->where('uniqueProcessId = ?', [$uniqueProcessId]);
        
        return $qb;
    }

    /**
     * Returns process metadata by ID
     * 
     * @param string $metadataId Metadata ID
     */
    public function getProcessMetadataById(string $metadataId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata')
            ->where('metadataId = ?', [$metadataId])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Returns process metadata by unique process ID and metadata title
     * 
     * @param string $uniqueProcessId Unique process ID
     * @param string $title Metadata title
     */
    public function getProcessMetadataByTitleAndUniqueProcessId(string $uniqueProcessId, string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata')
            ->where('uniqueProcessId = ?', [$uniqueProcessId])
            ->andWhere('title = ?', [$title])
            ->execute();

        return $qb->fetch();
    }

    /**
     * Composes an instance of QueryBuilder for process metadata values for given $metadataId
     * 
     * @param string $metadataId Metadata ID
     */
    public function composeQueryForProcessMetadataValues(string $metadataId): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata_values')
            ->where('metadataId = ?', [$metadataId]);

        return $qb;
    }

    /**
     * Inserts a new metadata value
     * 
     * @param array $data Data array
     */
    public function insertNewMetadataValue(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_metadata_values', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Removes metadata values for metadata ID
     * 
     * @param string $metadataId Metadata ID
     */
    public function removeMetadataValuesForMetadataId(string $metadataId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_metadata_values')
            ->where('metadataId = ?', [$metadataId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Removes metadata
     * 
     * @param string $metadataId Metadata ID
     */
    public function removeMetadata(string $metadataId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_metadata')
            ->where('metadataId = ?', [$metadataId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Inserts new metadata
     * 
     * @param array $data Metadata data
     */
    public function insertNewMetadata(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_metadata', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }
}

?>