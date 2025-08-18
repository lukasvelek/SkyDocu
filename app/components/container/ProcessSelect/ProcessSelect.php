<?php

namespace App\Components\ProcessSelect;

use App\Constants\ProcessColorCombos;
use App\Core\Http\HttpRequest;
use App\Managers\Container\ProcessManager;
use App\Repositories\Container\ProcessRepository;
use App\UI\AComponent;

/**
 * ProcessSelect is a component that displays available processes and allows to start them
 * 
 * @author Lukas Velek
 */
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
        
        $code = '<div class="row">';
        
        $maxTilesInRow = 6;
        
        $row = 0;
        foreach($tiles as $tile) {
            if(($row + 1) <= $maxTilesInRow) {
                $code .= '<div class="col-md-2">' . $tile . '</div>';
                $row++;
            } else {
                $row = 0;
                $code .= '</div><div class="row">';
            }
        }
        
        $template->process_tiles = $code;
        
        return $template->render()->getRenderedContent();
    }

    public static function createFromComponent(AComponent $component) {}

    /**
     * Gets all available processes
     */
    private function getProcesses() {
        $qb = $this->processRepository->composeQueryForAvailableProcesses();
        $qb->andWhere('isEnabled = 1')
            ->execute();

        $processes = [];
        while($row = $qb->fetchAssoc()) {
            $definition = json_decode(base64_decode($row['definition']), true);
            
            $processes[] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'processId' => $row['processId'],
                'colorCombo' => $definition['colorCombo']
            ];
        }

        $this->processes = $processes;
    }

    /**
     * Fills templates for all process tiles, renders them and returns an array with their code
     */
    private function getProcessTiles(): array {
        $tiles = [];

        foreach($this->processes as $process) {
            $title = $process['title'];
            $description = $process['description'];
            $processId = $process['processId'];
            $colorCombo = $process['colorCombo'];

            $tileTemplate = $this->getTemplate(__DIR__ . '\\process-tile.html');

            $tileTemplate->process_id = $processId;
            $tileTemplate->process_title = $title;
            $tileTemplate->process_description = $description;
            $tileTemplate->process_start_link = $this->getProcessStartLink($processId);
            $tileTemplate->color = ProcessColorCombos::getColor($colorCombo);
            $tileTemplate->bg_color = ProcessColorCombos::getBackgroundColor($colorCombo);

            $tiles[] = $tileTemplate->render()->getRenderedContent();
        }

        return $tiles;
    }

    /**
     * Returns a process start link for given $processId
     * 
     * @param string $processId Process ID
     */
    private function getProcessStartLink(string $processId): string {
        return $this->createFullURLString('User:NewProcess', 'startProcess', ['processId' => $processId]);
    }
}

?>