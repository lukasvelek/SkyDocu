<?php

namespace App\Entities;

use App\Exceptions\GeneralException;

/**
 * ContainerEntity represent a single container (entity)
 * 
 * @author Lukas Velek
 */
class ContainerEntity extends AEntity {
    private string $containerId;
    private string $title;
    private string $description;
    private string $userId;
    private int $status;
    private string $dateCreated;
    private int $environment;
    private bool $canShowContainerReferent;
    private ?string $permanentFlashMessage;
    private int $dbSchema;

    /**
     * @var array<int, ContainerDatabaseEntity> $databases
     */
    private array $databases;

    /**
     * Class constructor
     * 
     * @param string $containerId
     * @param string $title
     * @param string $description
     * @param string $userId
     * @param int $status
     * @param string $dateCreated
     * @param int $environment
     * @param bool $canShowContainerReferent
     * @param ?string $permanentFlashMessage
     * @param int $dbSchema
     */
    public function __construct(
        string $containerId,
        string $title,
        string $description,
        string $userId,
        int $status,
        string $dateCreated,
        int $environment,
        bool $canShowContainerReferent,
        ?string $permanentFlashMessage,
        int $dbSchema
    ) {
        $this->containerId = $containerId;
        $this->title = $title;
        $this->description = $description;
        $this->userId = $userId;
        $this->status = $status;
        $this->dateCreated = $dateCreated;
        $this->environment = $environment;
        $this->canShowContainerReferent = $canShowContainerReferent;
        $this->permanentFlashMessage = $permanentFlashMessage;
        $this->dbSchema = $dbSchema;

        $this->databases = [];
    }

    /**
     * Returns container's ID
     */
    public function getId(): string {
        return $this->containerId;
    }

    /**
     * Returns container's title
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * Returns container's description
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Returns container's author's ID
     */
    public function getUserId(): string {
        return $this->userId;
    }

    /**
     * Returns container's status
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * Returns container's date of creation
     */
    public function getDateCreated(): string {
        return $this->dateCreated;
    }

    /**
     * Returns container's environment
     */
    public function getEnvironment(): int {
        return $this->environment;
    }

    /**
     * Returns whether container's referent can be displayed
     */
    public function canShowContainerReferent(): bool {
        return $this->canShowContainerReferent;
    }

    /**
     * Returns container's permanent flash message
     */
    public function getPermanentFlashMessage(): ?string {
        return $this->permanentFlashMessage;
    }

    /**
     * Returns container's database schema
     */
    public function getDbSchema(): int {
        return $this->dbSchema;
    }

    /**
     * Returns all container's databases
     * 
     * @return array<int, ContainerDatabaseEntity>
     */
    public function getDatabases(): array {
        return $this->databases;
    }

    /**
     * Returns container's default database
     */
    public function getDefaultDatabase(): ?ContainerDatabaseEntity {
        foreach($this->databases as $database) {
            if($database->isDefault()) {
                return $database;
            }
        }

        return null;
    }

    /**
     * Adds container databases
     * 
     * @param array<int, ContainerDatabaseEntity> $databases
     */
    public function addContainerDatabases(array $databases) {
        foreach($databases as $database) {
            if(!($database instanceof ContainerDatabaseEntity)) {
                throw new GeneralException('Array \'$databases\' contains entity that is not an instance of ContainerDatabaseEntity.');
            }

            $this->databases[] = $database;
        }
    }

    public static function createEntityFromDbRow(mixed $row): ?static {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, [
            'containerId' => 'string',
            'title' => 'string',
            'description' => 'string',
            'userId' => 'string',
            'status' => 'int',
            'dateCreated' => 'string',
            'environment' => 'int',
            'canShowContainerReferent' => 'bool',
            'permanentFlashMessage' => '?string',
            'dbSchema' => 'int'
        ]);

        $obj = new self(
            $row->containerId,
            $row->title,
            $row->description,
            $row->userId,
            $row->status,
            $row->dateCreated,
            $row->environment,
            $row->canShowContainerReferent,
            $row->permanentFlashMessage,
            $row->dbSchema
        );

        return $obj;
    }
}

?>