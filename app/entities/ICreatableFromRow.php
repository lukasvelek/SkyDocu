<?php

namespace App\Entities;

/**
 * Common interface for all entities that can be created from raw database row
 * 
 * @author Lukas Velek
 */
interface ICreatableFromRow {
    /**
     * Creates entity for raw database row
     * 
     * @param mixed $row Raw database row
     */
    static function createEntityFromDbRow(mixed $row): ?static;
}

?>