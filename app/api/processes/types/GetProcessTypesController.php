<?php

namespace App\Api\Processes\Types;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetProcessTypesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_PROCESS_TYPES)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setAllowedProperties([
            'typeId',
            'typeKey',
            'title',
            'description',
            'isEnabled'
        ]);

        $results = $this->getResults([$this, 'getProcessTypes'], 'typeId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::PROCESS_TYPES);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of process types
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    protected function getProcessTypes(int $limit, int $offset): array {
        $qb = $this->container->processRepository->composeQueryForProcessTypes();

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $types = [];
        while($row = $qb->fetchAssoc()) {
            $types[] = DatabaseRow::createFromDbRow($row);
        }

        return $types;
    }
}

?>