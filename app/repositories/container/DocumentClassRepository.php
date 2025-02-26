<?php

namespace App\Repositories\Container;

use App\Repositories\ARepository;

class DocumentClassRepository extends ARepository {
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

    public function composeQueryForClassesForGroups(array $groupIds) {
        $qb = $this->qb(__METHOD__);

        $qb->select(['classId'])
            ->from('document_class_group_rights')
            ->where($qb->getColumnInValues('groupId', $groupIds));

        return $qb;
    }

    public function getVisibleClassesForGroups(array $groupIds) {
        $qb = $this->composeQueryForClassesForGroups($groupIds)
            ->andWhere('canView = 1');

        $qb->execute();

        $classes = [];
        while($row = $qb->fetchAssoc()) {
            $classes[] = $row['classId'];
        }

        return $classes;
    }

    public function getDocumentClassById(string $classId) {
        $qb = $this->composeQueryForClasses();
        $qb->andWhere('classId = ?', [$classId])
            ->execute();

        return $qb->fetch();
    }

    public function getDocumentClassByTitle(string $title) {
        $qb = $this->composeQueryForClasses();
        $qb->andWhere('title = ?', [$title])
            ->execute();

        return $qb->fetch();
    }
}

?>