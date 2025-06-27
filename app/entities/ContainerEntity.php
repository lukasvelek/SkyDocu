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
    private bool $canShowContainerReferent;
    private ?string $permanentFlashMessage;
    private bool $isInDistribution;

    /**
     * @var array<int, ContainerDatabaseEntity> $databases
     */
    private array $databases;

    /**
     * Class constructor
     * 
     * @param string $containerId Container ID
     * @param string $title Title
     * @param string $description Description
     * @param string $userId User ID
     * @param int $status Status
     * @param string $dateCreated Date created
     * @param bool $canShowContainerReferent Can show container referent?
     * @param ?string $permanentFlashMessage Permanent flash message
     * @param bool $isInDistribution Is in distribution?
     */
    public function __construct(
        string $containerId,
        string $title,
        string $description,
        string $userId,
        int $status,
        string $dateCreated,
        bool $canShowContainerReferent,
        ?string $permanentFlashMessage,
        bool $isInDistribution
    ) {
        $this->containerId = $containerId;
        $this->title = $title;
        $this->description = $description;
        $this->userId = $userId;
        $this->status = $status;
        $this->dateCreated = $dateCreated;
        $this->canShowContainerReferent = $canShowContainerReferent;
        $this->permanentFlashMessage = $permanentFlashMessage;
        $this->isInDistribution = $isInDistribution;

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
     * Returns true if the container is in distribution
     */
    public function isInDistribution(): bool {
        return $this->isInDistribution;
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
            'canShowContainerReferent' => 'bool',
            'permanentFlashMessage' => '?string',
            'isInDistribution' => 'bool'
        ]);

        $obj = new self(
            $row->containerId,
            $row->title,
            $row->description,
            $row->userId,
            $row->status,
            $row->dateCreated,
            $row->canShowContainerReferent,
            $row->permanentFlashMessage,
            $row->isInDistribution
        );

        return $obj;
    }
}

?>