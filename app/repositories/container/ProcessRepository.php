<?php

namespace App\Repositories\Container;

use App\Constants\Container\ProcessStatus;
use App\Constants\Container\StandaloneProcesses;
use App\Repositories\ARepository;

class ProcessRepository extends ARepository {
    public function commonComposeQuery(bool $onlyNotFinished = true) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('processes');

        if($onlyNotFinished) {
            $qb->andWhere('status = ?', [ProcessStatus::IN_PROGRESS]);
        }

        return $qb;
    }

    public function insertNewProcess(string $processId, array $data) {
        $keys = ['processId'];
        $values = [$processId];
        foreach($data as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $qb = $this->qb(__METHOD__);

        $qb->insert('processes', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function updateProcess(string $processId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('processes')
            ->set($data)
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getProcessesForDocument(string $documentId, bool $activeOnly = true) {
        $qb = $this->qb(__METHOD__);
        
        $qb->select(['*'])
            ->from('processes')
            ->where('documentId = ?', [$documentId]);

        if($activeOnly) {
            $qb->andWhere($qb->getColumnNotInValues('status', [ProcessStatus::FINISHED, ProcessStatus::CANCELED]));
        }

        $qb->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = $row;
        }

        return $processes;
    }

    public function getProcessById(string $processId) {
        $qb = $this->commonComposeQuery(false);

        $qb->andWhere('processId = ?', [$processId])
            ->execute();

        return $qb->fetch();
    }

    public function getActiveProcessCountForDocuments(array $documentIds) {
        $qb = $this->qb(__METHOD__);

        $types = StandaloneProcesses::getAllConstants();

        $qb->select(['documentId', 'COUNT(processId) AS cnt'])
            ->from('processes')
            ->where($qb->getColumnInValues('documentId', $documentIds))
            ->andWhere($qb->getColumnNotInValues('status', [ProcessStatus::FINISHED, ProcessStatus::CANCELED]))
            ->andWhere($qb->getColumnInValues('type', $types));

        $qb->execute();

        $result = [];
        while($row = $qb->fetchAssoc()) {
            $result[$row['documentId']] = $row['cnt'];
        }

        return $result;
    }

    public function insertNewProcessComment(string $commentId, string $processId, string $userId, string $text) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_comments', ['commentId', 'processId', 'userId', 'description'])
            ->values([$commentId, $processId, $userId, $text])
            ->execute();

        return $qb->fetchBool();
    }

    public function insertNewProcessHistoryEntry(string $entryId, array $data) {
        $keys = [];
        $values = [];

        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $keys[] = 'entryId';
        $values[] = $entryId;

        $qb = $this->qb(__METHOD__);

        $qb->insert('process_metadata_history', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function insertNewProcessData(string $entryId, string $processId, string $data) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_data', ['entryId', 'processId', 'data'])
            ->values([$entryId, $processId, $data])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForProcessData() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_data');

        return $qb;
    }

    public function getProcessDataForProcess(string $processId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['data'])
            ->from('process_data')
            ->where('processId = ?', [$processId])
            ->execute();

        $data = [];
        while($row = $qb->fetchAssoc()) {
            $data = unserialize($row['data']);
        }

        return $data;
    }

    public function composeQueryForStandaloneProcesses() {
        $qb = $this->commonComposeQuery(false);
        
        $types = array_keys(StandaloneProcesses::getAll());

        $qb->andWhere($qb->getColumnInValues('type', $types));

        return $qb;
    }

    public function composeQueryForProcessTypes() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_types');

        return $qb;
    }

    public function updateProcessType(string $typeKey, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb->update('process_types')
            ->set($data)
            ->where('typeKey = ?', [$typeKey])
            ->execute();

        return $qb->fetchBool();
    }

    public function insertNewProcessType(string $typeId, string $typeKey, string $title, string $description, bool $isEnabled = true) {
        $qb = $this->qb(__METHOD__);

        $qb->insert('process_types', ['typeId', 'typeKey', 'title', 'description', 'isEnabled'])
            ->values([$typeId, $typeKey, $title, $description, $isEnabled])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteProcessTypeByTypeKey(string $typeKey) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_types')
            ->where('typeKey = ?', [$typeKey])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteProcessDataById(string $processId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_data')
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteProcessById(string $processId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('processes')
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteProcessCommentsForProcessId(string $processId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_comments')
            ->where('processId = ?', [$processId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForProcessMetadata() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata');

        return $qb;
    }

    public function composeQueryForProcessMetadataListValues() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata_list_values');

        return $qb;
    }

    public function getLastMetadataEnumValueKey(string $metadataId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['metadataKey'])
            ->from('process_metadata_list_values')
            ->where('metadataId = ?', [$metadataId])
            ->orderBy('metadataKey', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch('metadataKey');
    }

    public function createNewMetadataEnumValue(array $data) {
        $keys = [];
        $values = [];
        foreach($data as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
        }

        $qb = $this->qb(__METHOD__);

        $qb->insert('process_metadata_list_values', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function getMetadataEnumValueById(string $valueId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_metadata_list_values')
            ->where('valueId = ?', [$valueId])
            ->execute();

        return $qb->fetch();
    }

    public function updateMetadataEnumValue(string $valueId, string $title) {
        $qb = $this->qb(__METHOD__);

        $qb->update('process_metadata_list_values')
            ->set(['title' => $title])
            ->where('valueId = ?', [$valueId])
            ->execute();

        return $qb->fetchBool();
    }

    public function composeQueryForProcessComments(string $processId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('process_comments')
            ->where('processId = ?', [$processId]);

        return $qb;
    }

    public function deleteProcessCommentById(string $commentId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_comments')
            ->where('commentId = ?', [$commentId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteProcessMetadataEnumValue(string $valueId) {
        $qb = $this->qb(__METHOD__);

        $qb->delete()
            ->from('process_metadata_list_values')
            ->where('valueId = ?', [$valueId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>