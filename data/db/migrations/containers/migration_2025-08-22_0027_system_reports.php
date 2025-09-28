<?php

namespace App\Data\Db\Migrations\Containers;

use App\Constants\Container\ReportRightOperations;
use App\Constants\Container\SystemReports;
use App\Core\DB\AContainerBaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_08_22_0027_system_reports extends AContainerBaseMigration {
    public function up(): TableSchema {
        return $this->getTableSchema();
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $reportKeyToIdPair = [];
        
        $reports = $seed->seed('reports');

        foreach(array_keys(SystemReports::getAll()) as $key) {
            $data = SystemReports::getMetadataForCreation($key);
            $data['reportId'] = $this->getId();
            $data['userId'] = $this->getTechnicalUserId();

            $reportKeyToIdPair[$key] = $data['reportId'];

            $reports->add($data);
        }

        $reportRights = $seed->seed('report_rights');

        foreach($reportKeyToIdPair as $key => $reportId) {
            foreach(ReportRightOperations::getAll() as $right => $rightText) {
                $reportRights->add([
                    'rightId' => $this->getId(),
                    'reportId' => $reportId,
                    'entityId' => $this->getAdministratorUserId(),
                    'entityType' => 1,
                    'operation' => $right
                ]);
            }
        }

        return $seed;
    }
}

?>