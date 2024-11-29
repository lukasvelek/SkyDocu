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
    private GridRepository $gr;

    public function __construct(Logger $logger, EntityManager $entityManager, GridRepository $gr) {
        parent::__construct($logger, $entityManager);

        $this->gr = $gr;
    }

    public function getGridConfigurationForGridName(string $gridName) {
        $row = $this->gr->getGridConfigurationForGridName($gridName);

        if($row === null) {
            return null;
        }

        return DatabaseRow::createFromDbRow($row);
    }

    public function hasGridConfiguration(string $gridName) {
        return $this->getGridConfigurationForGridName($gridName) !== null;
    }

    public function createGridConfiguration(string $gridName, array $columns) {
        if(!$this->gr->insertGridConfiguration($gridName, $columns)) {
            throw new GeneralException('Database error.');
        }
    }

    public function updateGridConfiguration(string $gridName, array $columns) {
        if(!$this->gr->updateGridConfiguration($gridName, $columns)) {
            throw new GeneralException('Database error.');
        }
    }

    public function deleteGridConfiguration(string $gridName) {
        if(!$this->gr->deleteGridConfiguration($gridName)) {
            throw new GeneralException('Database error.');
        }
    }

    public function composeQueryForGridConfigurations() {
        return $this->gr->composeQueryForGridConfigurations();
    }

    public function getGridsWithNoConfiguration(bool $forSelect) {
        $qb = $this->gr->composeQueryForGridConfigurations();
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