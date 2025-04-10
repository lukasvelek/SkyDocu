<?php

namespace App\Api\PeeQL;

use App\Api\APeeQLOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\Http\JsonResponse;

/**
 * PeeQLController handles all PeeQL API requests
 * 
 * @author Lukas Velek
 */
class PeeQLController extends APeeQLOperation {
    protected function run(): JsonResponse {
        $result = $this->processQuery();

        $this->logPeeQL(ExternalSystemLogObjectTypes::PEEQL);

        return new JsonResponse($result);
    }

    /**
     * Processes PeeQL query and returns the result as an array
     */
    private function processQuery(): array {
        $data = $this->get('peeql');
        return $this->peeql->execute($data);
    }
}

?>