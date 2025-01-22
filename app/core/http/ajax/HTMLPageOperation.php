<?php

namespace App\Core\Http\Ajax;

/**
 * Operation for updating an HTML element in the page around an AJAX call
 * 
 * @author Lukas Velek
 */
class HTMLPageOperation implements IAjaxOperation {
    public const MODE_DEFAULT = 1;
    public const MODE_APPEND = 2;

    private ?string $htmlEntityId;
    private ?string $jsonResponseObjectName;
    private int $mode;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->htmlEntityId = null;
        $this->jsonResponseObjectName = null;
        $this->mode = self::MODE_DEFAULT;
        return $this;
    }

    /**
     * Sets the mode - overwrite or append
     * 
     * @param int $mode Mode (see HTMLPageOperation constants)
     */
    public function setMode(int $mode = self::MODE_DEFAULT): static {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Sets the entity ID in the HTML page
     * 
     * @param string $entityId Entity ID in the HTML page
     */
    public function setHtmlEntityId(string $entityId): static {
        $this->htmlEntityId = $entityId;
        return $this;
    }

    /**
     * Sets JSON response object name
     * 
     * @param string $objectName Object name
     */
    public function setJsonResponseObjectName(string $objectName): static {
        $this->jsonResponseObjectName = $objectName;
        return $this;
    }

    public function build(): string {
        return '$("#' . $this->htmlEntityId . '").' . ($this->mode == self::MODE_DEFAULT ? 'html' : 'append') . '(obj.' . $this->jsonResponseObjectName . ');';
    }
}

?>