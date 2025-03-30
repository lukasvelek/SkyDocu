<?php

namespace App\Api\Processes\Types;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetProcessTypesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'typeId',
            'typeKey',
            'title',
            'description',
            'isEnabled'
        ]);

        $results = [];
        $properties = $this->processPropeties($this->get('properties'));

        $types = $this->getProcessTypes($this->get('limit'), $this->get('offset'));

        foreach($types as $type) {
            foreach($properties as $property) {
                $results[$type->typeId][$property] = $type->$property;
            }
        }

        $this->logRead(ExternalSystemLogObjectTypes::PROCESS_TYPES);

        return new JsonResponse(['data' => $results]);
    }

    /**
     * Returns an array of process types
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     */
    private function getProcessTypes(int $limit, int $offset): array {
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