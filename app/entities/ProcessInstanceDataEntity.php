<?php

namespace App\Entities;

use App\Exceptions\GeneralException;

/**
 * ProcessInstaceDataEntity represents data for process instance
 * 
 * @author Lukas Velek
 */
class ProcessInstanceDataEntity {
    /**
     * @var array<string, array> $forms
     */
    private array $forms;
    private int $workflowIndex;
    /**
     * @var array<string, array> $workflowHistory
     */
    private array $workflowHistory;

    /**
     * Class constructor
     * 
     * @param array $forms Form data
     * @param int $workflowIndex Workflow index
     * @param array $workflowHistory Workflow history
     */
    public function __construct(array $forms, int $workflowIndex, array $workflowHistory) {
        $this->forms = $forms;
        $this->workflowIndex = $workflowIndex;
        $this->workflowHistory = $workflowHistory;
    }

    /**
     * Returns workflow index
     */
    public function getWorkflowIndex(): int {
        return $this->workflowIndex;
    }

    /**
     * Increments workflow index
     */
    public function incrementWorkflowIndex() {
        $this->workflowIndex++;
    }

    /**
     * Decrements workflow index
     */
    public function decrementWorkflowIndex() {
        $this->workflowIndex--;
    }

    /**
     * Returns form data for a user
     * 
     * @param string $userId User ID
     */
    public function getFormForUser(string $userId): ?array {
        $form = null;
        foreach($this->forms as $_form) {
            if($_form['userId'] == $userId) {
                $form = $_form;
                break;
            }
        }

        return $form;
    }

    /**
     * Returns form data by index
     * 
     * @param int $index Index
     */
    public function getFormByIndex(int $index) {
        if($index >= count($this->forms)) {
            return null;
        }

        return $this->forms[$index];
    }

    /**
     * Returns all form data
     */
    public function getForms(): array {
        return $this->forms;
    }

    /**
     * Adds form data
     * 
     * @param string $userId User ID
     * @param array $data Form data
     */
    public function addFormData(string $userId, array $data) {
        $this->forms[] = [
            'userId' => $userId,
            'data' => $data
        ];
    }

    /**
     * Returns workflow history for user
     * 
     * @param string $userId User ID
     */
    public function getWorkflowHistoryForUser(string $userId): ?array {
        $entry = null;
        foreach($this->workflowHistory as $wh) {
            if($wh['userId'] == $userId) {
                $entry = $wh;
                break;
            }
        }

        return $entry;
    }

    /**
     * Returns all workflow history
     */
    public function getWorkflowHistory(): array {
        return $this->workflowHistory;
    }

    /**
     * Adds new workflow history entry
     * 
     * @param string $userId User ID
     * @param string $operation Operation
     */
    public function addNewWorkflowHistoryEntry(string $userId, string $operation) {
        $data = self::createNewWorkflowHistoryEntry($operation, null);

        $this->workflowHistory[] = [
            'userId' => $userId,
            'data' => $data
        ];
    }

    /**
     * Serializes data
     */
    public function serialize(): string {
        $data = [
            'forms' => $this->forms,
            'workflowIndex' => $this->workflowIndex,
            'workflowHistory' => $this->workflowHistory
        ];

        return serialize($data);
    }

    /**
     * Creates an instance of ProcessInstanceDataEntity from serialized data
     * 
     * @param string $serializedData Serialized data
     */
    public static function createFromSerializedData(string $serializedData): static {
        $data = unserialize($serializedData);

        if(!array_key_exists('forms', $data)) {
            throw new GeneralException('No forms data are defined.');
        }
        if(!array_key_exists('workflowIndex', $data)) {
            throw new GeneralException('No workflow index is defined.');
        }
        if(!array_key_exists('workflowHistory', $data)) {
            throw new GeneralException('No workflow history is defined.');
        }

        return new self($data['forms'], $data['workflowIndex'], $data['workflowHistory']);
    }

    /**
     * Creates a new workflow history entry array for appending
     * 
     * @param string $operation Operation
     * @param ?string $date Custom date or null for current date
     */
    public static function createNewWorkflowHistoryEntry(string $operation, ?string $date): array {
        return [
            'operation' => $operation,
            'date' => ($date ?? date('Y-m-d H:i:s'))
        ];
    }
}

?>