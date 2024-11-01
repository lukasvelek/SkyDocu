<?php

namespace App\Repositories\Container;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class DocumentClassRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllClasses() {
        $qb = $this->composeQueryForClasses();

        $qb->execute();

        $classes = [];
        while($row = $qb->fetchAssoc()) {
            $classes[] = $row;
        }

        return $classes;
    }

    public function composeQueryForClasses() {
        $qb = $this->qb(__METHOD__);

        $qb->select(['*'])
            ->from('document_classes');

        return $qb;
    }

    public function composeQueryForClassesForGroup(string $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['classId'])
            ->from('document_class_group_rights')
            ->where('groupId = ?', [$groupId]);

        return $qb;
    }

    public function getVisibleClassesForGroup(string $groupId) {
        $qb = $this->composeQueryForClassesForGroup($groupId)
            ->andWhere('canView = 1');

        $qb->execute();

        $classes = [];
        while($row = $qb->fetchAssoc()) {
            $classes[] = $row['classId'];
        }

        return $classes;
    }
}

?>