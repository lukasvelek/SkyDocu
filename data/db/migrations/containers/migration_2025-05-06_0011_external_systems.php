<?php

namespace App\Data\Db\Migrations\Containers;

use App\Constants\Container\ExternalSystemRightsOperations;
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

        $systemId = $this->getId('external_systems', 'systemId');

        $seed->seed('external_systems')
            ->add([
                'systemId' => $systemId,
                'title' => 'Default SkyDocu External System',
                'description' => 'This is the default SkyDocu external system.',
                'login' => $this->getUniqueHash(32, 'external_systems', 'login'),
                'password' => HashManager::hashPassword('DefaultSkyDocuPassword_26701!'),
                'isEnabled' => '1',
                'isSystem' => '1'
            ]);

        $esr = $seed->seed('external_system_rights');

        foreach(ExternalSystemRightsOperations::getAll() as $key => $value) {
            $esr->add([
                'rightId' => $this->getId('external_system_rights', 'rightId'),
                'systemId' => $systemId,
                'operationName' => $key,
                'isEnabled' => '1'
            ]);
        }

        return $seed;
    }
}

?>