<?php

namespace App\Entities;

/**
 * ProcessEntity describes a general process
 * 
 * @author Lukas Velek
 */
class ProcessEntity extends AEntity {
    public const DEFINITION_COLOR_COMBO = 'colorCombo';
    public const DEFINITION_FORMS = 'forms';
    public const DEFINITION_NAME = 'name';
    public const DEFINITION_FORM_ACTOR = 'actor';
    public const DEFINITION_FORM_FORM = 'form';

    public const METADATA_DEFINITION_NAME = 'name';
    public const METADATA_DEFINITION_LABEL = 'label';
    public const METADATA_DEFINITION_DESCRIPTION = 'description';
    public const METADATA_DEFINITION_TYPE = 'type';
    public const METADATA_DEFINITION_DEFAULT_VALUE = 'defaultValue';
    public const METADATA_DEFINITION_IS_EDITABLE = 'isEditable';

    private string $processId;
    private string $uniqueProcessId;
    private string $title;
    private string $description;
    private int $version;
    private int $status;
    private string $userId;
    private string $dateCreated;
    private array $definition;
    private array $metadataDefinition;
    private bool $isVisible;
    
    // FROM DEFINITION
    private string $colorCombo;
    private array $workflow = [];
    private string $name = 'Process';
    private array $forms = [];
    // END FROM DEFINITION

    /**
     * Class constructor
     * 
     * @param string $processId Process ID
     * @param string $uniqueProcessId Unique process ID
     * @param string $title Title
     * @param string $description Description
     * @param int $version Version
     * @param int $status Status
     * @param string $userId User ID
     * @param string $dateCreated Date created
     * @param array $definition Definition
     * @param array $metadataDefinition Metadata definition
     */
    public function __construct(
        string $processId,
        string $uniqueProcessId,
        string $title,
        string $description,
        int $version,
        int $status,
        string $userId,
        string $dateCreated,
        array $definition,
        array $metadataDefinition,
        bool $isVisible
    ) {
        $this->processId = $processId;
        $this->uniqueProcessId = $uniqueProcessId;
        $this->title = $title;
        $this->description = $description;
        $this->version = $version;
        $this->status = $status;
        $this->userId = $userId;
        $this->dateCreated = $dateCreated;
        $this->definition = $definition;
        $this->metadataDefinition = $metadataDefinition;
        $this->isVisible = $isVisible;
        
        // process definition
        if(array_key_exists(self::DEFINITION_COLOR_COMBO, $this->definition)) {
            $this->colorCombo = $this->definition[self::DEFINITION_COLOR_COMBO];
        }
        if(array_key_exists(self::DEFINITION_NAME, $this->definition)) {
            $this->name = $this->definition[self::DEFINITION_NAME];
        }
        if(array_key_exists(self::DEFINITION_FORMS, $this->definition)) {
            $this->forms = $this->definition[self::DEFINITION_FORMS];
        }

        $actors = [];
        foreach($this->forms as $form) {
            $actors[] = $form[self::DEFINITION_FORM_ACTOR];
        }

        $this->workflow = $actors;
    }

    /**
     * Returns process ID
     */
    public function getId(): string {
        return $this->processId;
    }

    /**
     * Returns unique process ID
     */
    public function getUniqueProcessId(): string {
        return $this->uniqueProcessId;
    }

    /**
     * Returns title
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * Returns description
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * Returns version
     */
    public function getVersion(): int {
        return $this->version;
    }

    /**
     * Returns status
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * Returns author ID
     */
    public function getUserId(): string {
        return $this->userId;
    }

    /**
     * Returns date created
     */
    public function getDateCreated(): string {
        return $this->dateCreated;
    }

    /**
     * Returns workflow
     */
    public function getWorkflow(): array {
        return $this->workflow;
    }

    /**
     * Returns all forms
     */
    public function getForms(): array {
        return $this->forms;
    }

    /**
     * Returns form for actor or null
     * 
     * @param string $actor Actor name
     */
    public function getFormForActor(string $actor): ?array {
        $form = null;

        foreach($this->forms as $_form) {
            if($_form[self::DEFINITION_FORM_ACTOR] == $actor) {
                $form = $_form[self::DEFINITION_FORM_ACTOR];
            }
        }

        return $form;
    }

    /**
     * Returns process name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Returns color combo
     */
    public function getColorCombo(): string {
        return $this->colorCombo;
    }

    /**
     * Returns definition
     */
    public function getDefinition(): array {
        return $this->definition;
    }

    /**
     * Returns metadata definition
     */
    public function getMetadataDefinition(): array {
        return $this->metadataDefinition;
    }

    /**
     * Returns metadata definition for metadata name or null
     * 
     * @param string $name Metadata name
     */
    public function getMetadataDefinitionForMetadataName(string $name): ?array {
        $data = null;

        foreach($this->metadataDefinition as $md) {
            if($md[self::METADATA_DEFINITION_NAME] == $name) {
                $data = $md;
            }
        }

        return $data;
    }

    /**
     * Returns true if process is visible or false if not
     */
    public function isVisible(): bool {
        return $this->isVisible;
    }

    public static function createEntityFromDbRow(mixed $row): ?static {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, [
            'processId' => 'string',
            'uniqueProcessId' => 'string',
            'title' => 'string',
            'description' => 'string',
            'userId' => 'string',
            'definition' => '?string',
            'version' => 'int',
            'status' => 'int',
            'dateCreated' => 'string',
            'metadataDefinition' => '?string',
            'isVisible' => 'int'
        ]);

        return new self(
            $row->processId,
            $row->uniqueProcessId,
            $row->title,
            $row->description,
            $row->version,
            $row->status,
            $row->userId,
            $row->dateCreated,
            json_decode(base64_decode($row->definition), true) ?? [],
            json_decode(base64_decode($row->metadataDefinition), true) ?? [],
            ($row->isVisible == 1)
        );
    }
}

?>