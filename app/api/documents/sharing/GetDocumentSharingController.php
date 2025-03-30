<?php

namespace App\Api\Documents\Sharing;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetDocumentSharingController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_DOCUMENT_SHARINGS)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setAllowedProperties([
            'sharingId',
            'documentId',
            'authorUserId',
            'userId',
            'dateValidUntil',
            'dateCreated'
        ]);

        $results = $this->getResults([$this, 'getSharings'], 'sharingId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::DOCUMENT_SHARING);

        return new JsonResponse(['data' => $results]);
    }

    protected function getSharings(int $limit, int $offset): array {
        $qb = $this->container->documentRepository->composeQueryForSharedDocuments();

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $shares = [];
        while($row = $qb->fetchAssoc()) {
            $shares[] = DatabaseRow::createFromDbRow($row);
        }

        return $shares;
    }
}

?>