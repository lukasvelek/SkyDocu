<?php

namespace App\Api\TranasctionLog;

use App\Api\AReadAPIOperation;
use App\Constants\Container\ExternalSystemLogObjectTypes;
use App\Constants\Container\ExternalSystemRightsOperations;
use App\Core\DB\DatabaseRow;
use App\Core\Http\JsonResponse;

class GetTransactionLogController extends AReadAPIOperation {
    protected function run(): JsonResponse {
        if(!$this->checkRight(ExternalSystemRightsOperations::READ_TRANSACTION_LOG)) {
            return new JsonResponse(['error' => 'Operation is not allowed.']);
        }

        $this->setAllowedProperties([
            'tranasctionId',
            'userId',
            'callingMethod',
            'dateCreated'
        ]);

        $results = $this->getResults([$this, 'getTransactionLog'], 'transactionId', $this->get('limit'), $this->get('offset'));

        $this->logRead(ExternalSystemLogObjectTypes::TRANSACTION_LOG);

        return new JsonResponse(['data' => $results]);
    }

    protected function getTransactionLog(int $limit, int $offset): array {
        $qb = $this->app->transactionLogRepository->composeQueryForTransactionLog($this->containerId);

        $this->appendWhereConditions($qb);

        $qb->limit($limit)
            ->offset($offset)
            ->execute();

        $entries = [];
        while($row = $qb->fetchAssoc()) {
            $entries[] = DatabaseRow::createFromDbRow($row);
        }

        return $entries;
    }
}

?>