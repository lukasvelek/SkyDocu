<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Managers\EntityManager;

class migration_2025_04_18_0007_processes extends ABaseMigration {
    public function up(): TableSchema {
        $table = $this->getTableSchema();

        $table->create('processes')
            ->primaryKey('processId')
            ->varchar('uniqueProcessId')
            ->varchar('title')
            ->varchar('description')
            ->text('form')
            ->text('workflow')
            ->text('workflowConfiguration')
            ->varchar('userId')
            ->integer('status', 4)
            ->integer('version')
            ->datetimeAuto('dateCreated');

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $seed->seed('processes')
            // HOME OFFICE
            ->add([
                'processId' => $this->getId(EntityManager::PROCESSES),
                'uniqueProcessId' => $this->getId(EntityManager::PROCESSES_UNIQUE),
                'title' => 'Home Office',
                'description' => 'Home Office form',
                'form' => 'eyJuYW1lIjogImhvbWVPZmZpY2VGb3JtIiwiZWxlbWVudHMiOiBbeyJuYW1lIjogInJlYXNvbiIsInR5cGUiOiAidGV4dGFyZWEiLCJsYWJlbCI6ICJSZWFzb246IiwiYXR0cmlidXRlcyI6IFsicmVxdWlyZWQiXX0seyJuYW1lIjogImRhdGVGcm9tIiwidHlwZSI6ICJkYXRlIiwibGFiZWwiOiAiRGF0ZSBmcm9tOiIsImF0dHJpYnV0ZXMiOiBbInJlcXVpcmVkIl19LHsibmFtZSI6ICJkYXRlVG8iLCJ0eXBlIjogImRhdGUiLCJsYWJlbCI6ICJEYXRlIHRvOiIsImF0dHJpYnV0ZXMiOiBbInJlcXVpcmVkIl19XSwicmVkdWNlciI6ICJcXEFwcFxcQ29tcG9uZW50c1xcUHJvY2Vzc0Zvcm1cXFByb2Nlc3Nlc1xcUmVkdWNlcnNcXEhvbWVPZmZpY2VSZWR1Y2VyIn0=',
                'workflow' => 'a:1:{i:0;s:23:"$CURRENT_USER_SUPERIOR$";}',
                'workflowConfiguration' => 'a:1:{s:23:"$CURRENT_USER_SUPERIOR$";a:2:{i:0;s:6:"accept";i:1;s:6:"reject";}}',
                'userId' => $this->getTechnicalUserId(),
                'status' => 2,
                'version' => 1
            ])
            ->add([
                'processId' => $this->getId(EntityManager::PROCESSES),
                'uniqueProcessId' => $this->getId(EntityManager::PROCESSES_UNIQUE),
                'title' => 'Function Request',
                'description' => 'Function Request form',
                'form' => 'ew0KICJuYW1lIjogImZ1bmN0aW9uUmVxdWVzdCIsDQogImVsZW1lbnRzIjogWw0KICB7DQogICAibmFtZSI6ICJ0aXRsZSIsDQogICAidHlwZSI6ICJ0ZXh0IiwNCiAgICJsYWJlbCI6ICJUaXRsZToiLA0KICAgImF0dHJpYnV0ZXMiOiBbDQogICAgInJlcXVpcmVkIg0KICAgXQ0KICB9LA0KICB7DQogICAibmFtZSI6ICJkZXNjcmlwdGlvbiIsDQogICAidHlwZSI6ICJ0ZXh0YXJlYSIsDQogICAibGFiZWwiOiAiRGVzY3JpcHRpb246IiwNCiAgICJhdHRyaWJ1dGVzIjogWw0KICAgICJyZXF1aXJlZCINCiAgIF0NCiAgfQ0KIF0NCn0=',
                'workflow' => 'a:1:{i:0;s:16:"$ADMINISTRATORS$";}',
                'workflowConfiguration' => 'a:1:{s:16:"$ADMINISTRATORS$";a:2:{i:0;s:6:"finish";i:1;s:6:"cancel";}}',
                'userId' => $this->getTechnicalUserId(),
                'status' => 2,
                'version' => 1
            ])
        ;

        return $seed;
    }
}

?>