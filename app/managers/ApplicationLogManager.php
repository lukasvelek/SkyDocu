<?php

namespace App\Managers;

use App\Constants\ApplicationLogLevels;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\ApplicationLogRepository;
use Exception;

class ApplicationLogManager extends AManager {
    public ApplicationLogRepository $appLogRepository;

    public function __construct(Logger $logger, ApplicationLogRepository $appLogRepository) {
        parent::__construct($logger);

        $this->appLogRepository = $appLogRepository;
    }

    /**
     * Handles passed operation in a transaction
     * 
     * @param array $operations Operations
     */
    public function handleOperation(array $operations) {
        $contextId = null;

        foreach($operations as $operation) {
            $e = null;

            $this->appLogRepository->conn->handle(function() use ($operation, &$e) {
                $class = $operation[0];
                $method = $operation[1];
                $params = $operation[2];

                $e = new Exception();

                return $class->$method(...$params);
            });

            $stackTrace = null;
            $message = 'test';
            if($e !== null) {
                $stackTrace = $e->getTraceAsString();
                $message = $e->getMessage();
            }

            $this->createNewLogEntry($contextId, $stackTrace, __METHOD__, $message, ApplicationLogLevels::INFO);
        }
    }

    /**
     * Creates a new log entry
     * 
     * @param ?string $contextId Context ID or null
     * @param ?string $callStack Call stack or null
     * @param string $caller Caller
     * @param string $message Message
     * @param string $type Type
     */
    public function createNewLogEntry(?string $contextId, ?string $callStack, string $caller, string $message, string $type): string {
        if($contextId === null) {
            $contextId = $this->createId();
        }

        $entryId = $this->createId();

        $data = [
            'logId' => $entryId,
            'contextId' => $contextId,
            'caller' => $caller,
            'message' => $message,
            'type' => $type,
            'level' => ApplicationLogLevels::getConstByKey($type) ?? ApplicationLogLevels::INFO
        ];

        if($callStack !== null) {
            $data['callStack'] = htmlspecialchars($callStack);
        }

        if(!$this->appLogRepository->insertNewEntry($data)) {
            throw new GeneralException('Database error.');
        }

        return $contextId;
    }
}