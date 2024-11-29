<?php

namespace App\Entities;

use App\UI\LinkBuilder;

class UserEntity extends AEntity {
    private string $id;
    private string $username;
    private string $fullname;
    private ?string $email;
    private string $dateCreated;

    public function __construct(string $id, string $username, string $fullname, ?string $email, string $dateCreated) {
        $this->id = $id;
        $this->username = $username;
        $this->fullname = $fullname;
        $this->email = $email;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getFullname() {
        return $this->fullname;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['userId' => 'string', 'username' => 'string', 'fullname' => 'string', 'email' => '?string', 'dateCreated' => 'string']);

        return new self($row->userId, $row->username, $row->fullname, $row->email, $row->dateCreated);
    }
}

?>