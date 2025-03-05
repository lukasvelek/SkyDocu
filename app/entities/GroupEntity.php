<?php

namespace App\Entities;

class GroupEntity extends AEntity {
    private string $id;
    private string $title;
    private ?string $containerId;
    private string $dateCreated;

    public function __construct(string $id, string $title, ?string $containerId, string $dateCreated) {
        $this->id = $id;
        $this->title = $title;
        $this->containerId = $containerId;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getContainerId() {
        return $this->containerId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row): ?static {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);

        return new self($row->groupId, $row->title, $row->containerId, $row->dateCreated);
    }
}

?>