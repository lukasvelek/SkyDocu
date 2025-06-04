<?php

namespace App\Data\Db\Migrations;

use App\Core\DB\ABaseMigration;
use App\Core\DB\Helpers\TableSchema;
use App\Core\DB\Helpers\TableSeeding;

class migration_2025_05_29_0013_multiform_process_definition extends ABaseMigration {
    public function up(): TableSchema {
        return $this->getTableSchema();
    }

    public function down(): TableSchema {
        return $this->getTableSchema();
    }

    public function seeding(): TableSeeding {
        $seed = $this->getTableSeeding();

        $seed->seed('processes')
            // HOME OFFICE
            ->add([
                'processId' => $this->getId('processes'),
                'uniqueProcessId' => $this->getId('processes', 'uniqueProcessId'),
                'title' => 'Home Office',
                'description' => 'New Home Office Request',
                'userId' => $this->getTechnicalUserId(),
                'definition' => 'eyJjb2xvckNvbWJvIjoiYmx1ZSIsImZvcm1zIjpbeyJhY3RvciI6IiRDVVJSRU5UX1VTRVIkIiwiZm9ybSI6IntcclxuIFwibmFtZVwiOiBcImN1cnJlbnRVc2VyUmVxdWVzdFwiLFxyXG4gXCJlbGVtZW50c1wiOiBbXHJcbiAge1xyXG4gICBcIm5hbWVcIjogXCJyZWFzb25cIixcclxuICAgXCJsYWJlbFwiOiBcIlJlYXNvbjpcIixcclxuICAgXCJ0eXBlXCI6IFwidGV4dGFyZWFcIixcclxuICAgXCJhdHRyaWJ1dGVzXCI6IFtcclxuICAgIFwicmVxdWlyZWRcIlxyXG4gICBdXHJcbiAgfSxcclxuICB7XHJcbiAgIFwibmFtZVwiOiBcImRhdGVGcm9tXCIsXHJcbiAgIFwibGFiZWxcIjogXCJEYXRlIGZyb206XCIsXHJcbiAgIFwidHlwZVwiOiBcImRhdGVcIixcclxuICAgXCJhdHRyaWJ1dGVzXCI6IFtcclxuICAgIFwicmVxdWlyZWRcIlxyXG4gICBdXHJcbiAgfSxcclxuICB7XHJcbiAgIFwibmFtZVwiOiBcImRhdGVUb1wiLFxyXG4gICBcImxhYmVsXCI6IFwiRGF0ZSB0bzpcIixcclxuICAgXCJ0eXBlXCI6IFwiZGF0ZVwiLFxyXG4gICBcImF0dHJpYnV0ZXNcIjogW1xyXG4gICAgXCJyZXF1aXJlZFwiXHJcbiAgIF1cclxuICB9XHJcbiBdLFxyXG4gXCJyZWR1Y2VyXCI6IFwiXFxcXEFwcFxcXFxDb21wb25lbnRzXFxcXFByb2Nlc3NGb3JtXFxcXFByb2Nlc3Nlc1xcXFxSZWR1Y2Vyc1xcXFxIb21lT2ZmaWNlUmVkdWNlclwiXHJcbn0ifSx7ImFjdG9yIjoiJENVUlJFTlRfVVNFUl9TVVBFUklPUiQiLCJmb3JtIjoie1wibmFtZVwiOiBcInN1cGVyaW9yUHJvY2Vzc1wiLFwiZWxlbWVudHNcIjogW3tcIm5hbWVcIjogXCJhY2NlcHRcIixcInR5cGVcIjogXCJhY2NlcHRCdXR0b25cIiwgXCJpbnN0YW5jZURlc2NyaXB0aW9uXCI6XCJBY2NlcHRlZCBIb21lIG9mZmljZSByZXF1ZXN0IVwifSx7XCJuYW1lXCI6IFwicmVqZWN0XCIsXCJ0eXBlXCI6IFwicmVqZWN0QnV0dG9uXCIsIFwiaW5zdGFuY2VEZXNjcmlwdGlvblwiOiBcIlJlamVjdGVkIEhvbWUgb2ZmaWNlIHJlcXVlc3QhXCJ9XX0ifV19',
                'version' => 1,
                'status' => 2
            ])
            // FUNCTION REQUEST
            ->add([
                'processId' => $this->getId('processes'),
                'uniqueProcessId' => $this->getId('processes', 'uniqueProcessId'),
                'title' => 'Function Request',
                'description' => 'New Function Request',
                'userId' => $this->getTechnicalUserId(),
                'definition' => 'eyJjb2xvckNvbWJvIjoicmVkIiwiZm9ybXMiOlt7ImFjdG9yIjoiJENVUlJFTlRfVVNFUiQiLCJmb3JtIjoie1xyXG4gXCJuYW1lXCI6IFwiZnVuY3Rpb25SZXF1ZXN0Rm9ybVwiLFxyXG4gXCJlbGVtZW50c1wiOiBbXHJcbiAge1xyXG4gICBcIm5hbWVcIjogXCJ0aXRsZVwiLFxyXG4gICBcImxhYmVsXCI6IFwiVGl0bGU6XCIsXHJcbiAgIFwidHlwZVwiOiBcInRleHRcIixcclxuICAgXCJhdHRyaWJ1dGVzXCI6IFtcclxuICAgIFwicmVxdWlyZWRcIlxyXG4gICBdXHJcbiAgfSxcclxuICB7XHJcbiAgIFwibmFtZVwiOiBcImRlc2NyaXB0aW9uXCIsXHJcbiAgIFwibGFiZWxcIjogXCJEZXNjcmlwdGlvbjpcIixcclxuICAgXCJ0eXBlXCI6IFwidGV4dGFyZWFcIixcclxuICAgXCJhdHRyaWJ1dGVzXCI6IFtcclxuICAgIFwicmVxdWlyZWRcIlxyXG4gICBdXHJcbiAgfVxyXG4gXVxyXG59In0seyJhY3RvciI6IiRBRE1JTklTVFJBVE9SUyQiLCJmb3JtIjoie1xyXG4gXCJuYW1lXCI6IFwiZnVuY3Rpb25SZXF1ZXN0Rm9ybVwiLFxyXG4gXCJlbGVtZW50c1wiOiBbXHJcbiAge1xyXG4gICBcIm5hbWVcIjogXCJjb21tZW50XCIsXHJcbiAgIFwibGFiZWxcIjogXCJDb21tZW50OlwiLFxyXG4gICBcInR5cGVcIjogXCJ0ZXh0YXJlYVwiLFxyXG4gICBcImF0dHJpYnV0ZXNcIjogW1xyXG4gICAgXCJyZXF1aXJlZFwiXHJcbiAgIF1cclxuICB9LFxyXG4gIHtcclxuICAgXCJuYW1lXCI6IFwiYWNjZXB0XCIsXHJcbiAgIFwidHlwZVwiOiBcImFjY2VwdEJ1dHRvblwiXHJcbiAgfSxcclxuICB7XHJcbiAgIFwibmFtZVwiOiBcInJlamVjdFwiLFxyXG4gICBcInR5cGVcIjogXCJyZWplY3RCdXR0b25cIlxyXG4gIH1cclxuIF1cclxufSJ9XX0=',
                'version' => 1,
                'status' => 2
            ])
            // CONTAINER REQUEST
            ->add([
                'processId' => $this->getId('processes'),
                'uniqueProcessId' => $this->getId('processes', 'uniqueProcessId'),
                'title' => 'Container Request',
                'description' => 'New Container Request',
                'userId' => $this->getTechnicalUserId(),
                'definition' => 'eyJjb2xvckNvbWJvIjoicmVkIiwiZm9ybXMiOlt7ImFjdG9yIjoiJENVUlJFTlRfVVNFUiQiLCJmb3JtIjoie1xyXG4gXCJuYW1lXCI6IFwiY29udGFpbmVyUmVxdWVzdEZvcm1cIixcclxuIFwiZWxlbWVudHNcIjogW1xyXG4gIHtcclxuICAgXCJuYW1lXCI6IFwiY29udGFpbmVyVGl0bGVcIixcclxuICAgXCJsYWJlbFwiOiBcIkNvbnRhaW5lciB0aXRsZTpcIixcclxuICAgXCJ0eXBlXCI6IFwidGV4dFwiLFxyXG4gICBcImF0dHJpYnV0ZXNcIjogW1xyXG4gICAgXCJyZXF1aXJlZFwiXHJcbiAgIF1cclxuICB9LFxyXG4gIHtcclxuICAgXCJuYW1lXCI6IFwicmVhc29uXCIsXHJcbiAgIFwibGFiZWxcIjogXCJSZWFzb246XCIsXHJcbiAgIFwidHlwZVwiOiBcInRleHRhcmVhXCIsXHJcbiAgIFwiYXR0cmlidXRlc1wiOiBbXHJcbiAgICBcInJlcXVpcmVkXCJcclxuICAgXVxyXG4gIH1cclxuIF1cclxufSJ9LHsiYWN0b3IiOiIkQURNSU5JU1RSQVRPUlMkIiwiZm9ybSI6IntcIm5hbWVcIjogXCJjb250YWluZXJSZXF1ZXN0UHJvY2Vzc1wiLFwiZWxlbWVudHNcIjogW3tcIm5hbWVcIjogXCJjb21tZW50XCIsXCJsYWJlbFwiOiBcIkNvbW1lbnQ6XCIsXCJ0eXBlXCI6IFwidGV4dGFyZWFcIixcImF0dHJpYnV0ZXNcIjogW1wicmVxdWlyZWRcIl19LHtcIm5hbWVcIjogXCJhY2NlcHRcIixcInR5cGVcIjogXCJzdWJtaXRcIiwgXCJ0ZXh0XCI6IFwiQWNjZXB0XCJ9LHtcIm5hbWVcIjogXCJyZWplY3RcIixcInR5cGVcIjogXCJyZWplY3RCdXR0b25cIn1dfSJ9XX0=',
                'version' => 1,
                'status' => 2
            ])
            // INVOICE
            ->add([
                'processId' => $this->getId('processes'),
                'uniqueProcessId' => $this->getId('processes', 'uniqueProcessId'),
                'title' => 'Invoice',
                'description' => 'New Invoice',
                'userId' => $this->getTechnicalUserId(),
                'definition' => '',
                'version' => 1,
                'status' => 2
            ])
        ;

        return $seed;
    }
}

?>