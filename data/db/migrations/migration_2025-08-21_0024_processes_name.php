<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_21_0024_processes_name extends ABaseMigration {
    public function up(): TableSchema {
        $schema = $this->getTableSchema();

        $schema->update('processes')
            ->varchar('name');

        return $schema;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $processes = $seed->seed('processes');

        $mapping = [
            'Function Request' => 'sys_functionRequest',
            'Home Office' => 'sys_homeOffice',
            'Container Request', 'sys_containerRequest',
            'Invoice' => 'sys_invoice'
        ];

        foreach($mapping as $title => $name) {
            $processes->update('title = \'' . $title . '\'', ['name' => $name]);
        }

        return $seed;
    }
}