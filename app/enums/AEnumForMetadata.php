<?php

namespace App\Enums;

use App\Core\Datatypes\ArrayList;

/**
 * Common predecessor to all system metadata enums
 * 
 * @author Lukas Velek
 */
abstract class AEnumForMetadata {
    public const KEY = 'key';
    public const TITLE = 'title';

    /**
     * Enum values memory cache
     */
    protected ArrayList $cache;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->cache = new ArrayList();
    }

    /**
     * Returns an array of values
     * 
     * The array must look like this:
     * [
     *  valueId => [
     *   self::KEY => metadataKey,
     *   self::TITLE => title
     *  ]
     * ]
     * 
     * valueId is the entity ID
     * metadataKey is the sorting key
     * title is the GUI title
     * 
     * @return ArrayList
     */
    abstract function getAll(): ArrayList;
}

?>