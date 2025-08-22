<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_21_0025_processes_name extends AContainerBaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('processes')
            ->varchar('name');

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $mapping = [
            'Function Request' => 'sys_functionRequest',
            'Home Office' => 'sys_homeOffice',
            'Container Request', 'sys_containerRequest',
            'Invoice' => 'sys_invoice'
        ];

        $processes = $seed->seed('processes');

        foreach($mapping as $title => $name) {
            $processes->update('title = \'' . $title . '\' AND status = 1', [
                'name' => $name
            ]);
        }

        return $seed;
    }
}

?>