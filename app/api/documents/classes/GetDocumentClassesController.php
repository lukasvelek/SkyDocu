<?php

namespace App\Api\Documents\Classes;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetDocumentClassesController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        $this->setAllowedProperties([
            'classId',
            'title'
        ]);

        $results = $this->getResults([$this, 'getClasses'], 'classId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::DOCUMENT_CLASSES);

        return new JsonResponse(['data' => $results]);
    }

    protected function getClasses(int $limit, int $offset): array {
        $qb = $this->container->documentClassRepository->composeQueryForClasses();

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $classes = [];
        while($row = $qb->fetchAssoc()) {
            $classes[] = DatabaseRow::createFromDbRow($row);
        }

        return $classes;
    }
}

?>