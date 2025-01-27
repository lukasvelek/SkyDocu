<?php

namespace App\Managers\Container;

use App\Constants\Container\GridNames;
use App\Core\DB\DatabaseRow;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\AManager;
use App\Managers\EntityManager;
use App\Repositories\Container\GridRepository;

class GridManager extends AManager {
    private GridRepository $gridRepository;

    public function __construct(Logger $logger, EntityManager $entityManager, GridRepository $gridRepository) {
        parent::__construct($logger, $entityManager);

        $this->gridRepository = $gridRepository;
    }

    public function getGridConfigurationForGridName(string $gridName) {
        $row = $this->gridRepository->getGridConfigurationForGridName($gridName);

        if($row === null) {
            return null;
        }

        return DatabaseRow::createFromDbRow($row);
    }

    public function hasGridConfiguration(string $gridName) {
        return $this->getGridConfigurationForGridName($gridName) !== null;
    }

    private function processColumnsArrayToString(array $columns) {
        return implode(';', $columns);
    }

    public function createGridConfiguration(string $gridName, array $columns) {
        $configurationId = $this->createId(EntityManager::C_GRID_CONFIGURATION);

        if(!$this->gridRepository->insertGridConfiguration($configurationId, $gridName, $this->processColumnsArrayToString($columns))) {
            throw new GeneralException('Database error.');
        }
    }

    public function updateGridConfiguration(string $gridName, array $columns) {
        if(!$this->gridRepository->updateGridConfiguration($gridName, $this->processColumnsArrayToString($columns))) {
            throw new GeneralException('Database error.');
        }
    }

    public function deleteGridConfiguration(string $gridName) {
        if(!$this->gridRepository->deleteGridConfiguration($gridName)) {
            throw new GeneralException('Database error.');
        }
    }

    public function composeQueryForGridConfigurations() {
        return $this->gridRepository->composeQueryForGridConfigurations();
    }

    public function getGridsWithNoConfiguration(bool $forSelect) {
        $qb = $this->gridRepository->composeQueryForGridConfigurations();
        $qb->execute();

        $gridsDb = [];
        while($row = $qb->fetchAssoc()) {
            $gridsDb[] = $row['gridName'];
        }

        $gridsToShow = [];
        foreach(GridNames::getAll() as $value => $text) {
            if(!in_array($value, $gridsDb)) {
                if($forSelect) {
                    $gridsToShow[] = [
                        'value' => $value,
                        'text' => $text
                    ];
                } else {
                    $gridsToShow[$value] = $text;
                }
            }
        }

        return $gridsToShow;
    }
}

?>