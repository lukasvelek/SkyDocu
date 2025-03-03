<?php

namespace App\Entities;

class SystemServiceEntity extends AEntity {
    private string $id;
    private string $title;
    private string $scriptPath;
    private ?string $dateStarted;
    private ?string $dateEnded;
    private int $status;
    private ?string $parentServiceId;
    private bool $isEnabled;
    private string $schedule;

    public function __construct(string $id, string $title, string $scriptPath, ?string $dateStarted, ?string $dateEnded, int $status, ?string $parentServiceId, bool $isEnabled, string $schedule) {
        $this->id = $id;
        $this->title = $title;
        $this->scriptPath = $scriptPath;
        $this->dateStarted = $dateStarted;
        $this->dateEnded = $dateEnded;
        $this->status = $status;
        $this->parentServiceId = $parentServiceId;
        $this->isEnabled = $isEnabled;
        $this->schedule = $schedule;
    }

    public function getId() {
        return $this->id;
    }
    
    public function getTitle() {
        return $this->title;
    }

    public function getScriptPath() {
        return $this->scriptPath;
    }

    public function getDateStarted() {
        return $this->dateStarted;
    }

    public function getDateEnded() {
        return $this->dateEnded;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getParentServiceId() {
        return $this->parentServiceId;
    }
    
    public function isEnabled() {
        return $this->isEnabled;
    }

    public function getSchedule() {
        return $this->schedule;
    }

    public static function createEntityFromDbRow(mixed $row): ?static {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, [
            'serviceId' => 'string',
            'title' => 'string',
            'scriptPath' => 'string',
            'dateStarted' => '?string',
            'dateEnded' => '?string',
            'status' => 'int',
            'parentServiceId' => '?string',
            'isEnabled' => 'bool',
            'schedule' => 'string'
        ]);

        return new self($row->serviceId, $row->title, $row->scriptPath, $row->dateStarted, $row->dateEnded, $row->status, $row->parentServiceId, $row->isEnabled, $row->schedule);
    }
}

?>