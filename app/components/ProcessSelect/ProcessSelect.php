<?php

namespace App\Components\ProcessSelect;

use App\Core\Http\HttpRequest;
use App\Managers\Container\ProcessManager;
use App\Repositories\Container\ProcessRepository;
use App\UI\AComponent;

class ProcessSelect extends AComponent {
    private ProcessManager $processManager;
    private ProcessRepository $processRepository;

    private array $processes;

    public function __construct(HttpRequest $request, ProcessManager $processManager, ProcessRepository $processRepository) {
        parent::__construct($request);

        $this->processManager = $processManager;
        $this->processRepository = $processRepository;
        
        $this->processes = [];
    }

    public function startup() {
        parent::startup();
        
        $this->getProcesses();
    }
    
    public function render() {
        $template = $this->getTemplate(__DIR__ . '\\template.html');

        $tiles = $this->getProcessTiles();
        
        return $template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}

    private function getProcesses() {
        $qb = $this->processRepository->composeQueryForAvailableProcesses();

        $qb->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $processes[] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'processId' => $row['processId']
            ];
        }

        $this->processes = $processes;
    }

    private function getProcessTiles() {
        $tiles = [];

        foreach($this->processes as $process) {
            $title = $process['title'];
            $description = $process['description'];
            $processId = $process['processId'];

            $tileTemplate = $this->getTemplate(__DIR__ . '\\process-tile.html');

            $tileTemplate->process_id = $processId;
            $tileTemplate->process_title = $title;
            $tileTemplate->process_description = $description;

            $tiles[] = $tileTemplate->render()->getRenderedContent();
        }

        return $tiles;
    }
}

?>