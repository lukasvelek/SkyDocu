<?php

namespace App\Core;

use App\Repositories\ApplicationLogRepository;

/**
 * ApplicationDatabaseLogger is used for logging information to the database
 * 
 * @author Lukas Velek
 */
class ApplicationDatabaseLogger {
    private DatabaseConnection $conn;
    private ?string $userId;
    private ApplicationLogRepository $appLogRepository;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $conn DatabaseConnection instance
     * @param ?string $userId User ID
     * @param ApplicationLogRepository $appLogRepository ApplicationLogRepository instance
     */
    public function __construct(
        DatabaseConnection $conn,
        ?string $userId,
        ApplicationLogRepository $appLogRepository
    ) {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->appLogRepository = $appLogRepository;
    }

    /**
     * Logs information to the database
     * 
     * @param string $message Message
     * @param string $method Method
     * @param ?string $stackTrace Stack trace or null
     * @param string $type Type
     */
    public function log(
        string $message,
        string $method,
        ?string $stackTrace,
        string $type
    ) {
        $logId = GUID::generate();
        $tsCreated = time();

        $data = [
            'logId' => $logId,
            'userId' => $this->userId,
            'message' => $message,
            'method' => $method,
            'type' => $type,
            'tsCreated' => $tsCreated
        ];

        if($stackTrace !== null) {
            $data['stackTrace'] = $stackTrace;
        }

        try {
            $this->appLogRepository->insertNewData($data);
        } catch(\Throwable|\mysqli_sql_exception $e) {

        }
    }
}