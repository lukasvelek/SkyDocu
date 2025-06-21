<?php

namespace App\UI\FormBuilder2;

use App\Exceptions\GeneralException;

/**
 * Represents a link to file
 * 
 * @author Lukas Velek
 */
class FileLink extends AElement {
    private string $name;
    private ?string $fileUrl = null;
    private ?string $fileName = null;
    
    public function __construct(string $name) {
        parent::__construct();

        $this->name = $name;
    }

    /**
     * Sets file URL
     * 
     * @param string $url File URL
     */
    public function setFileUrl(string $url): static {
        $this->fileUrl = $url;
        return $this;
    }

    /**
     * Sets file name
     * 
     * @param string $fileName File name
     */
    public function setFileName(string $fileName): static {
        $this->fileName = $fileName;
        return $this;
    }

    public function render() {
        if($this->fileUrl === null) {
            throw new GeneralException('No file URL is not set.');
        }

        $code = '<span name="' . $this->name . '" id="' . $this->name . '">';
        $code .= '<a class="link" href="' . $this->fileUrl . '">' . ($this->fileName ?? $this->fileUrl) . '</a>';
        $code .= '</span>';

        return $code;
    }
}

?>