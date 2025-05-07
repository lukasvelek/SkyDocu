<?php

namespace App\Data\Db\Migrations;

use App\Constants\ProcessColorCombos;
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
            ->datetimeAuto('dateCreated')
            ->varchar('colorCombo', 256, true);

        return $table;
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $colors = array_keys(ProcessColorCombos::getAll());

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
                'version' => 1,
                'colorCombo' => $colors[rand(0, count($colors) - 1)]
            ])
            // FUNCTION REQUEST
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
                'version' => 1,
                'colorCombo' => $colors[rand(0, count($colors) - 1)]
            ])
            // CONTAINER REQUEST
            ->add([
                'processId' => $this->getId(EntityManager::PROCESSES),
                'uniqueProcessId' => $this->getId(EntityManager::PROCESSES_UNIQUE),
                'title' => 'Container Request',
                'description' => 'Container Request form',
                'form' => 'ew0KICJuYW1lIjogImNvbnRhaW5lclJlcXVlc3QiLA0KICJlbGVtZW50cyI6IFsNCiAgew0KICAgIm5hbWUiOiAiY29udGFpbmVyTmFtZSIsDQogICAidHlwZSI6ICJ0ZXh0IiwNCiAgICJsYWJlbCI6ICJDb250YWluZXIgbmFtZToiLA0KICAgImF0dHJpYnV0ZXMiOiBbDQogICAgInJlcXVpcmVkIg0KICAgXQ0KICB9LA0KICB7DQogICAibmFtZSI6ICJlbnZpcm9ubWVudCIsDQogICAidHlwZSI6ICJzZWxlY3QiLA0KICAgInZhbHVlc0Zyb21Db25zdCI6ICJcXEFwcFxcQ29uc3RhbnRzXFxDb250YWluZXJFbnZpcm9ubWVudHMiLA0KICAgImxhYmVsIjogIkVudmlyb25tZW50IiwNCiAgICJhdHRyaWJ1dGVzIjogWw0KICAgICJyZXF1aXJlZCINCiAgIF0NCiAgfSwNCiAgew0KICAgIm5hbWUiOiAicmVhc29uIiwNCiAgICJ0eXBlIjogInRleHRhcmVhIiwNCiAgICJsYWJlbCI6ICJSZWFzb246IiwNCiAgICJhdHRyaWJ1dGVzIjogWw0KICAgICJyZXF1aXJlZCINCiAgIF0NCiAgfSwNCiAgew0KICAgIm5hbWUiOiAiYWRkaXRpb25hbE5vdGVzIiwNCiAgICJ0eXBlIjogInRleHRhcmVhIiwNCiAgICJsYWJlbCI6ICJBZGRpdGlvbmFsIG5vdGVzOiINCiAgfQ0KIF0NCn0=',
                'workflow' => 'a:2:{i:0;s:16:"$ADMINISTRATORS$";i:1;s:14:"$CURRENT_USER$";}',
                'workflowConfiguration' => 'a:2:{s:16:"$ADMINISTRATORS$";a:2:{i:0;s:6:"accept";i:1;s:6:"reject";}s:14:"$CURRENT_USER$";a:2:{i:0;s:6:"finish";i:1;s:6:"cancel";}}',
                'userId' => $this->getTechnicalUserId(),
                'status' => 2,
                'version' => 1,
                'colorCombo' => $colors[rand(0, count($colors) - 1)]
            ])
            // INVOICE
            ->add([
                'processId' => $this->getId(EntityManager::PROCESSES),
                'uniqueProcessId' => $this->getId(EntityManager::PROCESSES_UNIQUE),
                'title' => 'Invoice',
                'description' => 'Invoice form',
                'form' => 'ew0KICJuYW1lIjogImludm9pY2VGb3JtIiwNCiAiZWxlbWVudHMiOiBbDQogIHsNCiAgICJuYW1lIjogImludm9pY2VObyIsDQogICAidHlwZSI6ICJ0ZXh0IiwNCiAgICJsYWJlbCI6ICJJbnZvaWNlIE5vLjoiLA0KICAgImF0dHJpYnV0ZXMiOiBbDQogICAgInJlcXVpcmVkIiwNCiAgICAicmVhZG9ubHkiDQogICBdDQogIH0sDQogIHsNCiAgICJuYW1lIjogImNvbXBhbnkiLA0KICAgInR5cGUiOiAic2VsZWN0U2VhcmNoIiwNCiAgICJsYWJlbCI6ICJDb21wYW55OiIsDQogICAic2VhcmNoQnlMYWJlbCI6ICJDb21wYW55IG5hbWU6IiwNCiAgICJhY3Rpb25OYW1lIjogInNlYXJjaENvbXBhbmllcyIsDQogICAiYXR0cmlidXRlcyI6IFsNCiAgICAicmVxdWlyZWQiDQogICBdDQogIH0sDQogIHsNCiAgICJuYW1lIjogInN1bSIsDQogICAidHlwZSI6ICJudW1iZXIiLA0KICAgImxhYmVsIjogIlN1bToiLA0KICAgImF0dHJpYnV0ZXMiOiBbDQogICAgInJlcXVpcmVkIg0KICAgXQ0KICB9LA0KICB7DQogICAibmFtZSI6ICJzdW1DdXJyZW5jeSIsDQogICAidHlwZSI6ICJzZWxlY3QiLA0KICAgImxhYmVsIjogIlN1bSBjdXJyZW5jeToiLA0KICAgImF0dHJpYnV0ZXMiOiBbDQogICAgInJlcXVpcmVkIg0KICAgXQ0KICB9LA0KICB7DQogICAibmFtZSI6ICJmaWxlIiwNCiAgICJ0eXBlIjogImZpbGUiLA0KICAgImxhYmVsIjogIkludm9pY2UgZmlsZToiLA0KICAgImF0dHJpYnV0ZXMiOiBbDQogICAgInJlcXVpcmVkIg0KICAgXQ0KICB9DQogXSwNCiAicmVkdWNlciI6ICJcXEFwcFxcQ29tcG9uZW50c1xcUHJvY2Vzc0Zvcm1cXFByb2Nlc3Nlc1xccmVkdWNlcnNcXEludm9pY2VSZWR1Y2VyLnBocCINCn0=',
                'workflow' => 'a:2:{i:0;s:13:"$ACCOUNTANTS$";i:1;s:13:"$ACCOUNTANTS$";}',
                'workflowConfiguration' => 'a:2:{s:15:"$ACCOUNTANTS$_0";a:2:{i:0;s:6:"accept";i:1;s:6:"reject";}s:15:"$ACCOUNTANTS$_1";a:1:{i:0;s:7:"archive";}}',
                'userId' => $this->getTechnicalUserId(),
                'status' => 2,
                'version' => 1,
                'colorCombo' => $colors[rand(0, count($colors) - 1)]
            ])
        ;

        return $seed;
    }
}

?>