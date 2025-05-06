<?php

namespace App\Data\Db\Migrations\Containers;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Core\HashManager;

class migration_2025_05_06_0011_external_systems extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->update('external_systems')
            ->bool('isSystem')
            ->default('isSystem', 0);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $seed->seed('external_systems')
            ->add([
                'systemId' => $this->getId('external_systems', 'systemId'),
                'title' => 'Default SkyDocu External System',
                'description' => 'This is the default SkyDocu external system.',
                'login' => $this->getUniqueHash(32, 'external_systems', 'login'),
                'password' => HashManager::hashPassword('DefaultSkyDocuPassword_26701!'),
                'isEnabled' => '1',
                'isSystem' => '1'
            ]);

        return $seed;
    }
}

?>