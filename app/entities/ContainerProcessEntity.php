<?php

namespace App\Entities;

class ContainerProcessEntity extends AEntity {
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
    private string $userId;
    private array $definition;
    private int $status;
    private string $dateCreated;
    private bool $isEnabled;
    private bool $isVisible;
    private ?int $version;

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
     * @param string $userId User ID
     * @param array $definition Definition
     * @param int $status Status
     * @param string $dateCreated Date created
     * @param bool $isEnabled Is enabled
     * @param bool $isVisible Is visible
     * @param ?int $verison Version
     */
    public function __construct(
        string $processId,
        string $uniqueProcessId,
        string $title,
        string $description,
        string $userId,
        array $definition,
        int $status,
        string $dateCreated,
        bool $isEnabled,
        bool $isVisible,
        ?int $version
    ) {
        $this->processId = $processId;
        $this->uniqueProcessId = $uniqueProcessId;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->userId = $userId;
        $this->dateCreated = $dateCreated;
        $this->definition = $definition;
        $this->isVisible = $isVisible;
        $this->isEnabled = $isEnabled;
        $this->version = $version;

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
     * Returns true if process is visible or false if not
     */
    public function isVisible(): bool {
        return $this->isVisible;
    }

    /**
     * Returns true if process is enabled or false if not
     */
    public function isEnabled(): bool {
        return $this->isEnabled;
    }

    /**
     * Returns the process version
     */
    public function getVersion(): ?int {
        return $this->version;
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
            'definition' => 'string',
            'status' => 'int',
            'dateCreated' => 'string',
            'isEnabled' => 'int',
            'isVisible' => 'int',
            'version' => '?int'
        ]);

        return new self(
            $row->processId,
            $row->uniqueProcessId,
            $row->title,
            $row->description,
            $row->userId,
            json_decode(base64_decode($row->definition), true) ?? [],
            $row->status,
            $row->dateCreated,
            ($row->isEnabled == 1),
            ($row->isVisible == 1),
            $row->version
        );
    }
}

?>