<?php

namespace App\Repositories\Container;

use App\Constants\Container\ProcessInstanceOfficerTypes;
use App\Repositories\ARepository;
use PeeQL\Operations\Conditions\QueryCondition;
use PeeQL\Operations\QueryOperation;
use PeeQL\Result\QueryResult;
use QueryBuilder\QueryBuilder;

/**
 * ProcessInstanceRepository contains low-level database operations for process instances
 * 
 * @author Lukas Velek
 */
class ProcessInstanceRepository extends ARepository {
    /**
     * Composes a common QueryBuilder instance
     */
    public function commonComposeQuery(): QueryBuilder {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_instances');

        return $qb;
    }

    /**
     * Inserts a new process instance
     * 
     * @param array $data Process instance data
     */
    public function insertNewProcessInstance(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $keys = array_keys($data);
        $values = array_values($data);

        $qb->insert('process_instances', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Updates process instance
     * 
     * @param string $instanceId Instance ID
     * @param array $data Process instance data
     */
    public function updateProcessInstance(string $instanceId, array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->update('process_instances')
            ->set($data)
            ->where('instanceId = ?', [$instanceId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Retrieves a process instance from database
     * 
     * @param string $instanceId Process instance ID
     */
    public function getProcessInstanceById(string $instanceId): mixed {
        $qb = $this->commonComposeQuery();

        $qb->andWhere('instanceId = ?', [$instanceId])
            ->execute();

        return $qb->fetch();
    }

    public function get(QueryOperation $operation): QueryResult {
        return $this->processPeeQL('process_instances', $operation);
    }

    public function getMy(QueryOperation $operation): QueryResult {
        $operation->addCondition('currentOfficerType', ProcessInstanceOfficerTypes::USER, QueryCondition::TYPE_EQ);
        $operation->addCondition('currentOfficerId', $this->userId, QueryCondition::TYPE_EQ);
        return $this->processPeeQL('process_instances', $operation);
    }

    /**
     * Deletes process instance
     * 
     * @param string $instanceId Instance ID
     */
    public function deleteProcessInstance(string $instanceId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_instances')
            ->where('instanceId = ?', [$instanceId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Creates a new process instance file relation
     * 
     * @param string $relationId Relation ID
     * @param string $instanceId Instance ID
     * @param string $fileId File ID
     */
    public function createNewProcessInstanceFileRelation(string $relationId, string $instanceId, string $fileId): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_file_relation', ['relationId', 'instanceId', 'fileId'])
            ->values([$relationId, $instanceId, $fileId])
            ->execute();

        return $qb->fetchBool();
    }

    /**
     * Creates a new process instance log entry
     * 
     * @param array $data Data array
     */
    public function createNewProcessInstanceLogEntry(array $data): bool {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_instance_log', array_keys($data))
            ->values(array_values($data))
            ->execute();

        return $qb->fetchBool();
    }
}

?>