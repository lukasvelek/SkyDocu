<?php

namespace App\Core\DB\Helpers;

use App\Core\DB\Helpers\Seeding\CreateTableSeeding;

/**
 * TableSeeding helps with data seeding
 * 
 * @author Lukas Velek
 */
class TableSeeding {
    private array $seeds = [];

    /**
     * Returns an instance of CreateTableSeeding for table seeding
     * 
     * @param string $name Table name
     */
    public function seed(string $name): CreateTableSeeding {
        $create = new CreateTableSeeding($name);

        $this->seeds[$name] = &$create;

        return $create;
    }

    /**
     * Returns seeds
     */
    public function getSeeds(): array {
        return $this->seeds;
    }

    /**
     * Returns true if the seeds are empty
     */
    public function isEmpty(): bool {
        return empty($this->seeds);
    }
}

?>