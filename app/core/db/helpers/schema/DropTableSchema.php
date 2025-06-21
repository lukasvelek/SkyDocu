<?php

namespace App\Core\DB\Helpers\Schema;

/**
 * Drops given table
 * 
 * @author Lukas Velek
 */
class DropTableSchema extends ABaseTableSchema {
    public function getSQL(): array {
        return [
            'DROP TABLE IF EXISTS ' . $this->name
        ];
    }
}

?>