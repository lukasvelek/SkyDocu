<?php

namespace App\UI\GridBuilder2;

/**
 * Checkbox link entity
 * 
 * @author Lukas Velek
 */
class CheckboxLink {
    private string $name;
    /**
     * @var array $checkCallback Called with parameters: string $primaryKey
     */
    private array $checkCallback;
    /**
     * @var array $getLinkCallback Called with parameters: array $primaryKeys
     */
    private array $getLinkCallback;

    /**
     * Class constructor
     * 
     * @param string $name Name
     */
    public function __construct(string $name) {
        $this->name = $name;

        $this->checkCallback = [];
        $this->getLinkCallback = [];
    }

    /**
     * Sets callback for availability checking
     * 
     * @param callback $callback Callback called with parameters: string $primaryKey
     */
    public function setCheckCallback(callable $callback): static {
        $this->checkCallback[] = $callback;

        return $this;
    }

    /**
     * Sets callback for link generation
     * 
     * @param callback $callback Callback called with parameters: array $primaryKeys
     */
    public function setLinkCallback(callable $callback): static {
        $this->getLinkCallback[] = $callback;

        return $this;
    }

    /**
     * Returns callback for availability checking
     */
    public function getCheckCallback(): callable {
        return $this->checkCallback[0];
    }
    
    /**
     * Returns callback for link generation
     */
    public function getLinkCallback(): callable {
        return $this->getLinkCallback[0];
    }

    /**
     * Returns name
     */
    public function getName() {
        return $this->name;
    }
}