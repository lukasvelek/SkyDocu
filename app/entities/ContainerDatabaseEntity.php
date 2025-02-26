<?php

namespace App\Entities;

/**
 * ContainerDatabaseEntity represents a single container database (entity)
 * 
 * @author Lukas Velek
 */
class ContainerDatabaseEntity extends AEntity {
    private string $entryId;
    private string $containerId;
    private string $name;
    private bool $isDefault;

    /**
     * Class constructor
     * 
     * @param string $entryId
     * @param string $containerId
     * @param string $name
     * @param bool $isDefault
     */
    public function __construct(string $entryId, string $containerId, string $name, bool $isDefault) {
        $this->entryId = $entryId;
        $this->containerId = $containerId;
        $this->name = $name;
        $this->isDefault = $isDefault;
    }

    /**
     * Returns database's ID
     */
    public function getId(): string {
        return $this->entryId;
    }

    /**
     * Returns container's ID
     */
    public function getContainerId(): string {
        return $this->containerId;
    }

    /**
     * Returns database's name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Returns whether the database is default (created by system)
     */
    public function isDefault(): bool {
        return $this->isDefault;
    }

    public static function createEntityFromDbRow(mixed $row): static {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, [
            'entryId' => 'string',
            'containerId' => 'string',
            'name' => 'string',
            'isDefault' => 'bool'
        ]);

        return new self($row->entryId, $row->containerId, $row->name, $row->isDefault);
    }
}

?>