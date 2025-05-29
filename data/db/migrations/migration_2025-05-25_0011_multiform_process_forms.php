<?php

namespace App\Data\Db\Migrations;

use App\Constants\ProcessColorCombos;
use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;
use App\Managers\EntityManager;

class migration_2025_05_25_0011_multiform_process_forms extends ABaseMigration {
    public function up(): TableSchema {
        return $this->getTableSchema();
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
                'title' => 'Home office',
                'description' => 'Home office form',
                'userId' => $this->getTechnicalUserId(),
                'definition' => 'eyJjb2xvckNvbWJvIjoiYmx1ZSIsImZvcm1zIjpbeyJhY3RvciI6IiRDVVJSRU5UX1VTRVIkIiwiZm9ybSI6IntcclxuIFwibmFtZVwiOiBcImhvbWVPZmZpY2VSZXF1ZXN0XCIsXHJcbiBcImluc3RhbmNlRGVzY3JpcHRpb25cIjogXCJIb21lIE9mZmljZSByZXF1ZXN0IGZvciAkQ1VSUkVOVF9VU0VSX05BTUUkXCIsXHJcbiBcImVsZW1lbnRzXCI6IFtcclxuICB7XHJcbiAgIFwibmFtZVwiOiBcInJlYXNvblwiLFxyXG4gICBcImxhYmVsXCI6IFwiUmVhc29uOlwiLFxyXG4gICBcInR5cGVcIjogXCJ0ZXh0YXJlYVwiLFxyXG4gICBcImF0dHJpYnV0ZXNcIjogW1xyXG4gICAgXCJyZXF1aXJlZFwiXHJcbiAgIF1cclxuICB9LFxyXG4gIHtcclxuICAgXCJuYW1lXCI6IFwiZGF0ZUZyb21cIixcclxuICAgXCJsYWJlbFwiOiBcIkRhdGUgZnJvbTpcIixcclxuICAgXCJ0eXBlXCI6IFwiZGF0ZVwiLFxyXG4gICBcImF0dHJpYnV0ZXNcIjogW1xyXG4gICAgXCJyZXF1aXJlZFwiXHJcbiAgIF1cclxuICB9LFxyXG4gIHtcclxuICAgXCJuYW1lXCI6IFwiZGF0ZVRvXCIsXHJcbiAgIFwibGFiZWxcIjogXCJEYXRlIHRvOlwiLFxyXG4gICBcInR5cGVcIjogXCJkYXRlXCIsXHJcbiAgIFwiYXR0cmlidXRlc1wiOiBbXHJcbiAgICBcInJlcXVpcmVkXCJcclxuICAgXVxyXG4gIH1cclxuIF0sXHJcbiBcInJlZHVjZXJcIjogXCJcXFxcQXBwXFxcXENvbXBvbmVudHNcXFxcUHJvY2Vzc0Zvcm1cXFxcUHJvY2Vzc2VzXFxcXFJlZHVjZXJzXFxcXEhvbWVPZmZpY2VSZWR1Y2VyXCJcclxufSJ9LHsiYWN0b3IiOiIkQ1VSUkVOVF9VU0VSX1NVUEVSSU9SJCIsImZvcm0iOiJ7XHJcbiBcIm5hbWVcIjogXCJob21lT2ZmaWNlUmVxdWVzdFwiLFxyXG4gXCJpbnN0YW5jZURlc2NyaXB0aW9uXCI6IFwiUHJvY2Vzc2VkIEhvbWUgT2ZmaWNlIHJlcXVlc3RcIixcclxuIFwiZWxlbWVudHNcIjogW1xyXG4gIHtcclxuICAgXCJuYW1lXCI6IFwiYWNjZXB0XCIsXHJcbiAgIFwidHlwZVwiOiBcImFjY2VwdEJ1dHRvblwiXHJcbiAgfSxcclxuICB7XHJcbiAgIFwibmFtZVwiOiBcInJlamVjdFwiLFxyXG4gICBcInR5cGVcIjogXCJyZWplY3RCdXR0b25cIlxyXG4gIH1cclxuIF1cclxufSJ9XX0=',
                'version' => 1,
                'status' => 2
            ]);

        return $seed;
    }
}

?>